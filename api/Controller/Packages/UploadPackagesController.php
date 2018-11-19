<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller\Packages;

use Composer\Json\JsonFile;
use Contao\ManagerApi\Composer\Environment;
use Contao\ManagerApi\Config\UploadsConfig;
use Contao\ManagerApi\I18n\Translator;
use PhpZip\Exception\ZipException;
use PhpZip\ZipFile;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class UploadPackagesController
{
    const CHUNK_SIZE = 524288; // 500 KB

    /**
     * @var UploadsConfig
     */
    private $config;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(UploadsConfig $config, Environment $environment, Translator $translator, Filesystem $filesystem = null)
    {
        $this->config = $config;
        $this->environment = $environment;
        $this->translator = $translator;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    /**
     * @Route("/packages/uploads", methods={"GET"})
     */
    public function __invoke()
    {
        $uploads = $this->config->all();

        foreach ($uploads as $id => &$upload) {
            $upload['filesize'] = filesize($this->uploadPath($id));

            if ($upload['error']) {
                $upload['error'] = $this->translator->trans('upload.error.'.$upload['error']);
            } elseif (!$upload['success']) {
                $upload['error'] = $this->translator->trans('upload.error.partial');
            }
        }

        return new JsonResponse($uploads);
    }

    /**
     * @Route("/packages/uploads", methods={"POST"}, defaults={"form-data"=true})
     */
    public function upload(Request $request)
    {
        // Must be a HTML5 upload
        if ($request->files->count() > 0 && !$request->files->has('chunk')) {
            // TODO: handle HTML5 upload
            throw new \RuntimeException('HTML5 upload is not yet supported');
        }

        switch ($request->request->get('phase')) {
            case 'start':
                return new JsonResponse([
                    'status' => 'success',
                    'data' => [
                        'session_id' => $this->createChunk($request),
                        'end_offset' => self::CHUNK_SIZE,
                    ]
                ], Response::HTTP_CREATED);

            case 'upload':
                $this->addChunk(
                    $request->request->get('session_id'),
                    $request->request->get('start_offset'),
                    $request->files->get('chunk')
                );
                return new JsonResponse(['status' => 'success']);

            case 'finish':
                $id = $request->request->get('session_id');
                $this->finishUpload($id);
                return new JsonResponse(['status' => 'success', 'session_id' => $id]);
        }

        throw new \RuntimeException(sprintf('Invalid chunk phase "%s"', $request->request->get('phase')));
    }

    /**
     * @Route("/packages/uploads/{id}", methods={"DELETE"})
     */
    public function delete($id)
    {
        if (!$this->config->has($id)) {
            throw new NotFoundHttpException(sprintf('Unknown file ID "%s"', $id));
        }

        try {
            $this->filesystem->remove($this->uploadPath($id));
        } catch (IOException $e) {
            // Ignore if file could not be deleted
        }

        $this->config->remove($id);

        return new JsonResponse(['status' => 'success']);
    }

    private function createChunk(Request $request)
    {
        $id = bin2hex(random_bytes(8));

        $this->filesystem->touch($this->uploadPath($id));

        $this->config->set($id, [
            'name' => $request->request->get('name'),
            'size' => (int) $request->request->get('size'),
            'success' => false,
            'error' => null,
            'package' => null,
        ]);

        return $id;
    }

    private function addChunk($id, $offset, UploadedFile $file)
    {
        if (!$this->config->has($id)) {
            throw new NotFoundHttpException(sprintf('Unknown file ID "%s"', $id));
        }

        $fp = fopen($this->uploadPath($id), 'cb+');
        flock($fp, LOCK_EX);
        fseek($fp, $offset);
        fwrite($fp, file_get_contents($file->getPathname()), self::CHUNK_SIZE);
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    private function finishUpload($id)
    {
        $uploadFile = $this->uploadPath($id);
        $config = $this->config->get($id);

        if (null === $config || !$this->filesystem->exists($uploadFile)) {
            throw new NotFoundHttpException(sprintf('Unknown file ID "%s"', $id));
        }

        $size = filesize($uploadFile);

        if ($config['success'] || $config['error']) {
            throw new \RuntimeException('File has already be uploaded completely.');
        }

        if ($size !== $config['size']) {
            throw new \RuntimeException(sprintf('Incomplete upload ID "%s": %s instead of %s bytes', $id, $size, $config['size']));
        }

        try {
            $zipFile = new ZipFile();
            $zipFile->openFile($uploadFile);
        } catch (ZipException $e) {
            return $this->installError($id, 'zip', $e);
        }

        if (!in_array('composer.json', $zipFile->getListFiles())) {
            return $this->installError($id, 'composer');
        }

        try {
            $data = JsonFile::parseJson(
                $zipFile->getEntryContents('composer.json'),
                $config['name'] . '/composer.json'
            );
        } catch (\Exception $e) {
            return $this->installError($id, 'json', $e);
        }

        if (!isset($data['version'])) {
            return $this->installError($id, 'version');
        }

        // TODO: make sure file does not yet exist (same name and checksum)

        $config['success'] = true;
        $config['package'] = $data;

        $this->config->set($id, $config);

        return $config;
    }

    private function uploadPath($id)
    {
        return $this->environment->getUploadDir().'/'.$id;
    }

    private function installError($id, $error, \Exception $e = null)
    {
        $config = $this->config->get($id);

        $config['success'] = false;
        $config['error'] = $error;

        if ($e) {
            $config['exception'] = $e->getMessage();
        }

        $this->config->set($id, $config);

        return $config;
    }
}
