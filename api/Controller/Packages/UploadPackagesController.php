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
use Composer\Semver\Constraint\Constraint;
use Composer\Semver\Constraint\MultiConstraint;
use Composer\Semver\VersionParser;
use Contao\ManagerApi\Composer\Environment;
use Contao\ManagerApi\Config\UploadsConfig;
use Contao\ManagerApi\Exception\ApiProblemException;
use Contao\ManagerApi\I18n\Translator;
use Crell\ApiProblem\ApiProblem;
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
    const CHUNK_SIZE = 1048576; // 1MB

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
        $this->validateUploadSupport();

        $uploads = $this->config->all();

        foreach ($uploads as $id => &$upload) {
            $upload['filesize'] = filesize($this->uploadPath($id));

            if ($upload['error']) {
                $upload['error'] = $this->translator->trans('upload.error.'.$upload['error']);
            } elseif (!$upload['success']) {
                $upload['error'] = $this->translator->trans('upload.error.partial');
            }
        }

        return new JsonResponse(array_reverse($uploads));
    }

    /**
     * @Route("/packages/uploads", methods={"POST"}, defaults={"form-data"=true})
     */
    public function upload(Request $request)
    {
        $this->validateUploadSupport();

        // Must be a HTML5 upload
        if ($request->files->has('package')) {
            /** @var UploadedFile $file */
            $file = $request->files->get('package');

            $id = $this->createUpload(
                $file->getClientOriginalName(),
                $file->getSize()
            );

            $file->move($this->environment->getUploadDir(), $id);

            return $this->finishUpload($id);
        }

        switch ($request->request->get('phase')) {
            case 'start':
                $id = $this->createUpload(
                    $request->request->get('name'),
                    $request->request->getInt('size')
                );

                return new JsonResponse([
                    'status' => 'success',
                    'data' => [
                        'session_id' => $id,
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
                return $this->finishUpload($id);
        }

        throw new \RuntimeException(sprintf('Invalid chunk phase "%s"', $request->request->get('phase')));
    }

    /**
     * @Route("/packages/uploads/{id}", methods={"DELETE"})
     */
    public function delete($id)
    {
        $this->validateUploadSupport();

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

    private function createUpload($name, $size)
    {
        $id = bin2hex(random_bytes(8));

        $this->filesystem->touch($this->uploadPath($id));

        $this->config->set($id, [
            'id' => $id,
            'name' => $name,
            'size' => $size,
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
            $data = $this->getComposerInformation($uploadFile);

            if (null === $data) {
                return $this->installError($id, 'file');
            }
        } catch (\Exception $e) {
            return $this->installError($id, 'json', $e);
        }

        if (!isset($data['version'])) {
            return $this->installError($id, 'version');
        }

        list($vendor, $package) = explode('/', $data['name']);

        $config['success'] = true;
        $config['hash'] = sha1_file($uploadFile);
        $config['package'] = array_merge(
            $data,
            [
                'installation-source' => 'dist',
                'dist' => [
                    'shasum' => $config['hash'],
                    'type' => 'zip',
                    'url' => sprintf(
                        '/contao-manager/packages/%s__%s__%s__%s.zip',
                        $vendor,
                        $package,
                        (new VersionParser())->normalize($data['version']),
                        substr(sha1_file($uploadFile), 0, 8)
                    ),
                ],
            ]
        );

        $this->config->set($id, $config);

        return new JsonResponse(
            [
                'status' => 'success',
                'data' => $this->config->get($id),
            ]
        );
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

        return new JsonResponse($config);
    }

    private function validateUploadSupport()
    {
        if (!extension_loaded('zip')) {
            throw new ApiProblemException(
                (new ApiProblem('The artifact repository requires PHP\'s zip extension'))
                    ->setStatus(Response::HTTP_NOT_IMPLEMENTED)
            );
        }

        $packages = $this->environment
            ->getComposer()
            ->getRepositoryManager()
            ->getLocalRepository()
            ->getPackages()
        ;

        foreach ($packages as $package) {
            if ($package->getName() === 'contao/manager-plugin') {
                $require = new MultiConstraint([
                    new Constraint('>=', '2.7'),
                    new Constraint('=', 'dev-master'),
                ], false);

                if ($require->matches(new Constraint('=', $package->getVersion()))) {
                    return;
                }
            }
        }

        throw new ApiProblemException(
            (new ApiProblem('Must install contao/manager-plugin 2.7 or later to support artifacts.'))
                ->setStatus(Response::HTTP_NOT_IMPLEMENTED)
        );
    }

    /**
     * @see ArtifactRepository::getComposerInformation()
     */
    private function getComposerInformation(string $zipPath): ?array
    {
        $zip = new \ZipArchive();
        $zip->open($zipPath);

        if (!$zip->numFiles) {
            return null;
        }

        $foundFileIndex = $this->locateFile($zip, 'composer.json');

        if (false === $foundFileIndex) {
            return null;
        }

        $configurationFileName = $zip->getNameIndex($foundFileIndex);
        $composerFile = "zip://$zipPath#$configurationFileName";
        $json = file_get_contents($composerFile);

        return JsonFile::parseJson($json, $composerFile);
    }

    /**
     * @see ArtifactRepository::locateFile()
     */
    private function locateFile(\ZipArchive $zip, string $filename)
    {
        $indexOfShortestMatch = false;
        $lengthOfShortestMatch = -1;

        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $stat = $zip->statIndex($i);

            if (0 === strcmp(basename($stat['name']), $filename)) {
                $directoryName = \dirname($stat['name']);

                if ('.' === $directoryName) {
                    // If composer.json is in root directory, it has to be the one to use
                    return $i;
                }

                if (false !== strpos($directoryName, '\\') || false !== strpos($directoryName, '/')) {
                    // composer.json files below first directory are rejected
                    continue;
                }

                $length = \strlen($stat['name']);

                if (false === $indexOfShortestMatch || $length < $lengthOfShortestMatch) {
                    // Check it's not a directory
                    $contents = $zip->getFromIndex($i);

                    if (false !== $contents) {
                        $indexOfShortestMatch = $i;
                        $lengthOfShortestMatch = $length;
                    }
                }
            }
        }

        return $indexOfShortestMatch;
    }
}
