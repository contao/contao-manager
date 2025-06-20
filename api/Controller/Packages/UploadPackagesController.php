<?php

declare(strict_types=1);

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
use Composer\Util\Zip;
use Contao\ManagerApi\Composer\Environment;
use Contao\ManagerApi\Config\UploadsConfig;
use Contao\ManagerApi\Exception\ApiProblemException;
use Contao\ManagerApi\I18n\Translator;
use Crell\ApiProblem\ApiProblem;
use JsonSchema\Exception\ValidationException;
use JsonSchema\Validator;
use Seld\JsonLint\ParsingException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UploadPackagesController
{
    public const CHUNK_SIZE = 1048576;

    public function __construct(
        private readonly UploadsConfig $config,
        private readonly Environment $environment,
        private readonly Translator $translator,
        private readonly Filesystem $filesystem,
    ) {
    }

    #[Route(path: '/packages/uploads', methods: ['GET'])]
    #[IsGranted('ROLE_READ')]
    public function read(): JsonResponse
    {
        $this->validateUploadSupport();

        $uploads = $this->config->all();

        foreach ($uploads as $id => &$upload) {
            if (!$this->filesystem->exists($this->uploadPath($id))) {
                unset($uploads[$id]);
                $this->config->remove($id);
                continue;
            }

            $upload['filesize'] = filesize($this->uploadPath($id));

            if ($upload['error']) {
                $upload['error'] = $this->translator->trans('upload.error.'.$upload['error']);
            } elseif (!$upload['success']) {
                $upload['error'] = $this->translator->trans('upload.error.partial');
            }
        }

        return new JsonResponse(array_reverse($uploads));
    }

    #[Route(path: '/packages/uploads', methods: ['POST'], defaults: ['form-data' => true])]
    #[IsGranted('ROLE_INSTALL')]
    public function upload(Request $request): JsonResponse
    {
        $this->validateUploadSupport();

        // Must be a HTML5 upload
        if ($request->files->has('package')) {
            /** @var UploadedFile $file */
            $file = $request->files->get('package');

            $id = $this->createUpload(
                $file->getClientOriginalName(),
                $file->getSize(),
            );

            $file->move($this->environment->getUploadDir(), $id);

            return $this->finishUpload($id, $request->getPreferredLanguage());
        }

        switch ($request->request->get('phase')) {
            case 'start':
                $id = $this->createUpload(
                    $request->request->get('name'),
                    $request->request->getInt('size'),
                );

                return new JsonResponse(
                    [
                        'status' => 'success',
                        'data' => [
                            'session_id' => $id,
                            'end_offset' => self::CHUNK_SIZE,
                        ],
                    ],
                    Response::HTTP_CREATED,
                );

            case 'upload':
                $this->addChunk(
                    $request->request->get('session_id'),
                    $request->request->getInt('start_offset'),
                    $request->files->get('chunk'),
                );

                return new JsonResponse(['status' => 'success']);

            case 'finish':
                $id = $request->request->get('session_id');

                return $this->finishUpload($id, $request->getPreferredLanguage());
        }

        throw new \RuntimeException(\sprintf('Invalid chunk phase "%s"', $request->request->get('phase')));
    }

    #[Route(path: '/packages/uploads/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_INSTALL')]
    public function delete(string $id): JsonResponse
    {
        $this->validateUploadSupport();

        if (!$this->config->has($id)) {
            throw new NotFoundHttpException(\sprintf('Unknown file ID "%s"', $id));
        }

        try {
            $this->filesystem->remove($this->uploadPath($id));
        } catch (IOException) {
            // Ignore if file could not be deleted
        }

        $this->config->remove($id);

        return new JsonResponse(['status' => 'success']);
    }

    private function createUpload(string $name, int $size): string
    {
        /** @noinspection PhpUnhandledExceptionInspection */
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

    private function addChunk(string $id, int $offset, UploadedFile $file): void
    {
        if (!$this->config->has($id)) {
            throw new NotFoundHttpException(\sprintf('Unknown file ID "%s"', $id));
        }

        $fp = fopen($this->uploadPath($id), 'cb+');
        flock($fp, LOCK_EX);
        fseek($fp, $offset);
        fwrite($fp, file_get_contents($file->getPathname()), self::CHUNK_SIZE);
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    private function finishUpload(string $id, string|null $language = null): JsonResponse
    {
        $uploadFile = $this->uploadPath($id);
        $config = $this->config->get($id);

        if (null === $config || !$this->filesystem->exists($uploadFile)) {
            throw new NotFoundHttpException(\sprintf('Unknown file ID "%s"', $id));
        }

        $size = filesize($uploadFile);

        if ($config['success'] || $config['error']) {
            throw new \RuntimeException('File has already be uploaded completely.');
        }

        if ($size !== $config['size']) {
            throw new \RuntimeException(\sprintf('Incomplete upload ID "%s": %s instead of %s bytes', $id, $size, $config['size']));
        }

        try {
            $json = Zip::getComposerJson($uploadFile);

            if (null === $json) {
                return $this->installError($id, 'file');
            }
        } catch (\RuntimeException $e) {
            return $this->installError($id, 'file', $e);
        }

        try {
            $data = JsonFile::parseJson($json, $uploadFile.'#composer.json');
        } catch (ParsingException $exception) {
            return $this->installError($id, 'json', $exception);
        }

        try {
            $schemaFile = __DIR__.'/../../../vendor/composer/composer/res/composer-schema.json';

            // Prepend with file:// only when not using a special schema already (e.g. in the phar)
            if (!str_contains($schemaFile, '://')) {
                $schemaFile = 'file://'.$schemaFile;
            }

            $schema = (object) ['$ref' => $schemaFile];
            $schema->required = ['name', 'version'];

            $value = json_decode(json_encode($data), false);
            $validator = new Validator();
            $validator->validate($value, $schema, \JsonSchema\Constraints\Constraint::CHECK_MODE_EXCEPTIONS);
        } catch (ValidationException $exception) {
            return $this->installError($id, 'schema', $exception);
        }

        [$vendor, $package] = explode('/', (string) $data['name']);

        $config['success'] = true;
        $config['hash'] = sha1_file($uploadFile);
        $config['package'] = array_merge(
            $this->environment->mergeMetadata($data, $language),
            [
                'installation-source' => 'dist',
                'dist' => [
                    'shasum' => $config['hash'],
                    'type' => 'zip',
                    'url' => \sprintf(
                        '/contao-manager/packages/%s__%s__%s__%s.zip',
                        $vendor,
                        $package,
                        (new VersionParser())->normalize($data['version']),
                        substr(sha1_file($uploadFile), 0, 8),
                    ),
                ],
            ],
        );

        $this->config->set($id, $config);

        return new JsonResponse(
            [
                'status' => 'success',
                'data' => $this->config->get($id),
            ],
        );
    }

    private function uploadPath(string $id): string
    {
        return $this->environment->getUploadDir().'/'.$id;
    }

    private function installError(string $id, string $error, \Exception|null $e = null): JsonResponse
    {
        $config = $this->config->get($id);

        $config['success'] = false;
        $config['error'] = $error;

        if ($e instanceof \Exception) {
            $config['exception'] = $e->getMessage();
        }

        $this->config->set($id, $config);

        return new JsonResponse($config);
    }

    private function validateUploadSupport(): void
    {
        if (!$this->filesystem->exists($this->environment->getJsonFile())) {
            return;
        }

        if (!\extension_loaded('zip')) {
            throw new ApiProblemException((new ApiProblem("The artifact repository requires PHP's zip extension"))->setStatus(Response::HTTP_NOT_IMPLEMENTED));
        }

        $packages = $this->environment
            ->getComposer()
            ->getRepositoryManager()
            ->getLocalRepository()
            ->getPackages()
        ;

        foreach ($packages as $package) {
            if ('contao/manager-plugin' === $package->getName()) {
                $require = new MultiConstraint(
                    [
                        new Constraint('>=', '2.7'),
                        new Constraint('=', 'dev-main'),
                    ],
                    false,
                );

                if ($require->matches(new Constraint('=', $package->getVersion()))) {
                    return;
                }
            }
        }

        throw new ApiProblemException((new ApiProblem('Must install contao/manager-plugin 2.7 or later to support artifacts.'))->setStatus(Response::HTTP_NOT_IMPLEMENTED));
    }
}
