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
use Contao\ManagerApi\Composer\Environment;
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
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/server/contao", methods={"GET", "POST"})
 */
class ContaoController
{
    /**
     * @var ApiKernel
     */
    private $kernel;

    /**
     * @var ContaoApi
     */
    private $contaoApi;

    /**
     * @var ContaoConsole
     */
    private $contaoConsole;

    /**
     * @var ConsoleProcessFactory
     */
    private $processFactory;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(
        ApiKernel $kernel,
        ContaoApi $contaoApi,
        ContaoConsole $contaoConsole,
        ConsoleProcessFactory $processFactory,
        Environment $environment,
        LoggerInterface $logger = null,
        Filesystem $filesystem = null
    ) {
        $this->kernel = $kernel;
        $this->contaoApi = $contaoApi;
        $this->contaoConsole = $contaoConsole;
        $this->processFactory = $processFactory;
        $this->environment = $environment;
        $this->logger = $logger;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    public function __invoke(Request $request, ServerInfo $serverInfo): Response
    {
        if (!$serverInfo->getPhpExecutable()) {
            return new ApiProblemResponse(
                (new ApiProblem('Missing hosting configuration.', '/api/server/config'))
                    ->setStatus(Response::HTTP_SERVICE_UNAVAILABLE)
            );
        }

        try {
            $contaoVersion = $this->getContaoVersion();
        } catch (\RuntimeException $e) {
            if ($request->isMethod('POST')) {
                return new Response('', Response::HTTP_BAD_REQUEST);
            }

            if ($e instanceof ProcessFailedException) {
                return $this->createResponse(
                    [
                        'supported' => false,
                        'error' => $e->getMessage(),
                    ],
                    Response::HTTP_BAD_GATEWAY
                );
            }

            if ($e instanceof ProcessOutputException) {
                return $this->createResponse(
                    [
                        'supported' => false,
                        'error' => $this->contaoConsole->debugConsoleIssues(),
                    ],
                    Response::HTTP_BAD_GATEWAY
                );
            }

            $contaoVersion = null;
        }

        if (null === $contaoVersion) {
            $files = $this->getProjectFiles();
            $isEmpty = 0 === \count($files);

            if ($request->isMethod('POST')) {
                return $this->createDirectories(
                    $isEmpty ? null : $request->request->get('directory'),
                    $request->request->getBoolean('usePublicDir')
                );
            }

            return $this->createResponse([
                'is_empty' => $isEmpty,
            ]);
        }

        return $this->createResponse(
            [
                'version' => $contaoVersion,
                'cli' => [
                    'commands' => $this->contaoConsole->getCommandList(),
                ],
                'api' => [
                    'version' => $this->contaoApi->getVersion(),
                    'features' => $this->contaoApi->getFeatures(),
                    'commands' => $this->contaoApi->getCommands(),
                ],
                'supported' => version_compare($contaoVersion, '4.0.0', '>=') || 0 === strpos($contaoVersion, 'dev-'),
            ]
        );
    }

    private function createDirectories(?string $directory, bool $usePublicDir): Response
    {
        if ('' === \Phar::running()) {
            return new Response('', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $currentRoot = $this->kernel->getProjectDir();
        $targetRoot = $currentRoot;
        $publicDir = $currentRoot.'/'.($usePublicDir ? 'public' : 'web');

        if (null !== $directory) {
            if (false !== strpos($directory, '..')) {
                return new Response('', Response::HTTP_BAD_REQUEST);
            }

            if ($this->filesystem->exists($currentRoot.'/'.$directory)) {
                return new ApiProblemResponse(
                    (new ApiProblem('Target directory exists'))
                        ->setStatus(Response::HTTP_FORBIDDEN)
                );
            }

            $targetRoot = $currentRoot.'/'.$directory;
            $publicDir = $targetRoot.'/'.($usePublicDir ? 'public' : 'web');
            $this->filesystem->mkdir($targetRoot);
            $this->filesystem->mirror($this->kernel->getConfigDir(), $targetRoot.'/contao-manager');
            $this->filesystem->remove($this->kernel->getConfigDir());
        }

        $this->filesystem->mkdir($publicDir);

        // Create response before moving Phar, otherwise the JsonResponse class cannot be autoloaded
        $response = $this->createResponse([
            'project_dir' => $targetRoot,
            'public_dir' => ($usePublicDir ? 'public' : 'web'),
            'is_empty' => true,
        ], Response::HTTP_CREATED);

        $phar = \Phar::running(false);
        $this->filesystem->rename($phar, $publicDir.'/'.basename($phar));

        if ($this->filesystem->exists(\dirname($phar).'/.htaccess')) {
            $this->filesystem->rename(\dirname($phar).'/.htaccess', $publicDir.'/.htaccess');
        }

        if (0 === \count(array_diff(scandir(\dirname($phar), SCANDIR_SORT_NONE), ['.', '..']))) {
            $this->filesystem->remove(\dirname($phar));
        }

        return $response;
    }

    /**
     * Gets a list of files in the project root directory, excluding what is allowed to install Contao.
     */
    private function getProjectFiles(): array
    {
        $content = scandir($this->kernel->getProjectDir(), SCANDIR_SORT_NONE);

        return array_diff(
            $content,
            [
                '.',
                '..',
                '.env',
                '.env.local',
                '.git',
                '.idea',
                '.well-known',
                'cgi-bin',
                'contao-manager',
                'plesk-stat',
                '.bash_profile',
                '.bash_logout',
                '.bashrc',
                '.DS_Store',
                '.ftpquota',
                '.htaccess',
                'user.ini',
                basename(dirname(\Phar::running())), // Allow parent directory of the PHAR file (public dir)
                basename(\Phar::running()), // Allow the PHAR file itself
            ]
        );
    }

    /**
     * Tries to detect the Contao 4/3/2 version by analyzing the filesystem.
     */
    private function getContaoVersion(): ?string
    {
        if ($this->environment->hasPackage('contao/manager-bundle') || $this->filesystem->exists($this->processFactory->getContaoConsolePath())) {
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

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->info('Searching for Contao 2/3', ['files' => $files]);
        }

        foreach ($files as $file) {
            if ($this->filesystem->exists($file)) {
                try {
                    @include $file;
                } catch (\Throwable $e) {
                    // do nothing on error or exception
                }

                if (\defined('VERSION') && \defined('BUILD')) {
                    return VERSION.'.'.BUILD;
                }

                break;
            }
        }

        return null;
    }

    private function createResponse(array $data, int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse(array_merge([
            'version' => null,
            'cli' => [
                'commands' => [],
            ],
            'api' => [
                'version' => 0,
                'features' => [],
                'commands' => [],
            ],
            'supported' => false,
            'project_dir' => $this->kernel->getProjectDir(),
            'public_dir' => \basename($this->kernel->getPublicDir()),
            'is_empty' => false,
        ], $data), $status);
    }
}
