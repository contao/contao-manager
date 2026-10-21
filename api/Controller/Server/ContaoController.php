<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller\Server;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Exception\ProcessOutputException;
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Process\ContaoApi;
use Contao\ManagerApi\Process\ContaoConsole;
use Contao\ManagerApi\System\ServerInfo;
use Crell\ApiProblem\ApiProblem;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ContaoController
{
    public function __construct(
        private readonly ApiKernel $kernel,
        private readonly ContaoApi $contaoApi,
        private readonly ContaoConsole $contaoConsole,
        private readonly ConsoleProcessFactory $processFactory,
        private readonly LoggerInterface $logger,
        private readonly Filesystem $filesystem,
    ) {
    }

    #[Route(path: '/server/contao', methods: ['GET'])]
    #[IsGranted('ROLE_READ')]
    public function handle(Request $request, ServerInfo $serverInfo): Response
    {
        $properties = $this->getRequestedProperties($request);

        if (!$serverInfo->getPhpExecutable()) {
            return new ApiProblemResponse(
                (new ApiProblem('Missing hosting configuration.', '/api/server/config'))
                    ->setStatus(Response::HTTP_SERVICE_UNAVAILABLE),
            );
        }

        if (null !== $properties && [] === array_intersect($properties, ['version', 'supported', 'cli', 'api', 'config', 'conflicts'])) {
            return $this->createResponse([], Response::HTTP_OK, $properties);
        }

        $contaoVersion = null;

        try {
            $contaoVersion = $this->getContaoVersion();
        } catch (\RuntimeException $exception) {
            if (null !== ($response = $this->createVersionErrorResponse($request, $exception))) {
                return $response;
            }
        }

        if (null === $contaoVersion) {
            if ($request->isMethod('POST')) {
                return $this->createDirectories(
                    $request->request->get('directory'),
                    $request->request->getBoolean('usePublicDir'),
                );
            }

            $data = [];

            if (null === $properties || \in_array('conflicts', $properties, true)) {
                $data['conflicts'] = $this->getProjectFiles();
            }

            return $this->createResponse($data, Response::HTTP_OK, $properties);
        }

        return $this->createResponse($this->getContaoData($contaoVersion, $properties), Response::HTTP_OK, $properties);
    }

    #[Route(path: '/server/contao', methods: ['POST'])]
    #[IsGranted('ROLE_INSTALL')]
    public function update(Request $request, ServerInfo $serverInfo): Response
    {
        return $this->handle($request, $serverInfo);
    }

    private function getRequestedProperties(Request $request): array|null
    {
        if (!$request->isMethod('GET') || !$request->query->has('properties')) {
            return null;
        }

        $value = $request->query->all()['properties'];

        if (!\is_string($value)) {
            throw new BadRequestHttpException('Contao properties must be a comma-separated string.');
        }

        $properties = array_unique(array_map('trim', explode(',', $value)));

        if ([] !== array_diff($properties, array_keys($this->getDefaultData()))) {
            throw new BadRequestHttpException('Unknown Contao properties requested.');
        }

        return $properties;
    }

    private function createVersionErrorResponse(Request $request, \RuntimeException $exception): Response|null
    {
        if ($request->isMethod('POST')) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        if ($exception instanceof ProcessFailedException) {
            return $this->createResponse(
                ['supported' => false, 'error' => $exception->getMessage()],
                Response::HTTP_BAD_GATEWAY,
            );
        }

        if ($exception instanceof ProcessOutputException) {
            return $this->createResponse(
                [
                    'supported' => false,
                    'error' => $exception->getProcess()->getErrorOutput() ?: $exception->getProcess()->getOutput(),
                ],
                Response::HTTP_BAD_GATEWAY,
            );
        }

        return null;
    }

    private function getContaoData(string $version, array|null $properties): array
    {
        $data = [
            'version' => $version,
            'supported' => version_compare($version, '4.0.0', '>=') || str_starts_with($version, 'dev-'),
        ];

        if (null === $properties || \in_array('cli', $properties, true)) {
            $data['cli'] = ['commands' => (object) $this->contaoConsole->getCommandList()];
        }

        if (null === $properties || \in_array('api', $properties, true)) {
            $data['api'] = [
                'version' => $this->contaoApi->getVersion(),
                'features' => $this->contaoApi->getFeatures(),
                'commands' => $this->contaoApi->getCommands(),
            ];
        }

        if (null === $properties || \in_array('config', $properties, true)) {
            $data['config'] = (object) $this->contaoConsole->getConfig();
        }

        return $data;
    }

    private function createDirectories(string|null $directory, bool $usePublicDir): Response
    {
        if (!ApiKernel::isPhar()) {
            return new Response('', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $currentRoot = $this->kernel->getProjectDir();
        $targetRoot = $currentRoot;
        $publicDir = $currentRoot.'/'.($usePublicDir ? 'public' : 'web');

        if (null !== $directory) {
            if (str_contains($directory, '..')) {
                return new Response('', Response::HTTP_BAD_REQUEST);
            }

            if ($this->filesystem->exists($currentRoot.'/'.$directory)) {
                return new ApiProblemResponse(
                    (new ApiProblem('Target directory exists'))
                        ->setStatus(Response::HTTP_FORBIDDEN),
                );
            }

            $targetRoot = $currentRoot.'/'.$directory;
            $publicDir = $targetRoot.'/'.($usePublicDir ? 'public' : 'web');
            $this->filesystem->mkdir($targetRoot);
            $this->filesystem->mirror($this->kernel->getConfigDir(), $targetRoot.'/contao-manager');
            $this->filesystem->remove($this->kernel->getConfigDir());
        }

        $this->filesystem->mkdir($publicDir);

        // Create response before moving Phar, otherwise the JsonResponse class
        // cannot be autoloaded
        $response = $this->createResponse(
            [
                'project_dir' => $targetRoot,
                'public_dir' => ($usePublicDir ? 'public' : 'web'),
                'conflicts' => [],
            ],
            Response::HTTP_CREATED,
        );

        $phar = \Phar::running(false);
        $this->filesystem->rename($phar, $publicDir.'/'.basename($phar));

        if ($this->filesystem->exists(\dirname($phar).'/.htaccess')) {
            $this->filesystem->rename(\dirname($phar).'/.htaccess', $publicDir.'/.htaccess');
        }

        if ([] === array_diff(scandir(\dirname($phar), SCANDIR_SORT_NONE), ['.', '..'])) {
            $this->filesystem->remove(\dirname($phar));
        }

        return $response;
    }

    /**
     * Gets a list of files in the project root directory, excluding what is allowed
     * to install Contao.
     */
    private function getProjectFiles(): array
    {
        $content = scandir($this->kernel->getProjectDir());

        if (false === $content) {
            return [];
        }

        return array_values(array_diff(
            $content,
            [
                '.',
                '..',
                '.env',
                '.env.local',
                '.git',
                '.idea',
                '.ddev',
                '.well-known',
                'cgi-bin',
                'contao-manager',
                'plesk-stat',
                'public',
                'web',
                '.bash_profile',
                '.bash_logout',
                '.bashrc',
                '.DS_Store',
                '.ftpquota',
                '.htaccess',
                'user.ini',
                basename(\dirname(\Phar::running())), // Allow parent directory of the PHAR file (public dir)
                basename(\Phar::running()), // Allow the PHAR file itself
            ],
        ));
    }

    /**
     * Tries to detect the Contao 4/3/2 version by analyzing the filesystem.
     */
    private function getContaoVersion(): string|null
    {
        if ($this->filesystem->exists($this->processFactory->getContaoConsolePath())) {
            return $this->contaoConsole->getVersion();
        }

        // Required for Contao 2.11
        \define('TL_ROOT', $this->kernel->getProjectDir());

        $files = [
            $this->kernel->getProjectDir().'/system/constants.php',
            $this->kernel->getProjectDir().'/system/config/constants.php',
        ];

        // Test if the Phar was placed in the Contao 2/3 root
        if ('' !== ($phar = \Phar::running(false))) {
            $files[] = \dirname($phar).'/system/constants.php';
            $files[] = \dirname($phar).'/system/config/constants.php';
        }

        $this->logger->info('Searching for Contao 2/3', ['files' => $files]);

        foreach ($files as $file) {
            if ($this->filesystem->exists($file)) {
                try {
                    @include $file;
                } catch (\Throwable) {
                    // do nothing on error or exception
                }

                if (\defined('VERSION') && \defined('BUILD')) {
                    /** @noinspection PhpUndefinedConstantInspection */
                    return VERSION.'.'.BUILD;
                }

                break;
            }
        }

        return null;
    }

    private function createResponse(array $data, int $status = Response::HTTP_OK, array|null $properties = null): JsonResponse
    {
        $data = array_merge($this->getDefaultData(), $data);

        if (null !== $properties) {
            $data = array_intersect_key($data, array_flip($properties));
        }

        return new JsonResponse($data, $status);
    }

    private function getDefaultData(): array
    {
        return [
            'version' => null,
            'cli' => [
                'commands' => [],
            ],
            'api' => [
                'version' => 0,
                'features' => [],
                'commands' => [],
            ],
            'config' => new \stdClass(),
            'supported' => false,
            'conflicts' => [],
            'project_dir' => $this->kernel->getProjectDir(),
            'public_dir' => basename($this->kernel->getPublicDir()),
            'directory_separator' => \DIRECTORY_SEPARATOR,
        ];
    }
}
