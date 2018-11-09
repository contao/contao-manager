<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller\Server;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Config\ManagerConfig;
use Contao\ManagerApi\Exception\ProcessOutputException;
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Process\ContaoApi;
use Contao\ManagerApi\Process\ContaoConsole;
use Contao\ManagerApi\System\ServerInfo;
use Crell\ApiProblem\ApiProblem;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ContaoController extends Controller
{
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

    public function __construct(ContaoApi $contaoApi, ContaoConsole $contaoConsole, ConsoleProcessFactory $processFactory)
    {
        $this->contaoApi = $contaoApi;
        $this->contaoConsole = $contaoConsole;
        $this->processFactory = $processFactory;
    }

    /**
     * Gets response about Composer configuration and file validation.
     *
     * @return Response
     */
    public function __invoke(ManagerConfig $managerConfig, ServerInfo $serverInfo, Translator $translator)
    {
        if (!$managerConfig->has('server') || !$serverInfo->getPhpExecutable()) {
            return new ApiProblemResponse(
                (new ApiProblem('Missing hosting configuration.', '/api/server/config'))
                    ->setStatus(Response::HTTP_SERVICE_UNAVAILABLE)
            );
        }

        try {
            $contaoVersion = $this->getContaoVersion();
        } catch (\RuntimeException $e) {
            if ($e instanceof ProcessOutputException || $e instanceof ProcessFailedException) {
                return new ApiProblemResponse(
                    (new ApiProblem(
                        $translator->trans('integrity.contao_version.title')
                    ))->setDetail(
                        $translator->trans('integrity.contao_version.detail', ['output' => $e->getProcess()->getOutput()])
                    )
                );
            }

            $contaoVersion = null;
        }

        if (null === $contaoVersion) {
            if (0 === count($files = $this->getProjectFiles())) {
                return new JsonResponse(
                    [
                        'version' => null,
                        'api' => 0,
                    ]
                );
            }

            return new ApiProblemResponse(
                (new ApiProblem(
                    $translator->trans('integrity.contao_unknown.title')
                ))->setDetail(
                    $translator->trans('integrity.contao_unknown.detail', ['files' => ' - '.implode("\n - ", $files)])
                )
            );
        }

        return new JsonResponse(
            [
                'version' => $contaoVersion,
                'api' => $this->getApiVersion(),
                'supported' => version_compare($contaoVersion, '4.3.5', '>='),
            ]
        );
    }

    /**
     * Gets a list of files in the project root directory, excluding what is allowed to install Contao.
     *
     * @return array
     */
    private function getProjectFiles()
    {
        $content = scandir($this->get('kernel')->getContaoDir(), SCANDIR_SORT_NONE);
        $content = array_diff(
            $content,
            [
                '.',
                '..',
                '.git',
                '.idea',
                'cgi-bin',
                'contao-manager',
                'web',
                '.bash_profile',
                '.bash_logout',
                '.bashrc',
                '.DS_Store',
                '.ftpquota',
                '.htaccess',
                'user.ini',
            ]
        );

        return $content;
    }

    /**
     * Gets the Contao API version.
     *
     * @return int|null
     */
    private function getApiVersion()
    {
        try {
            return $this->contaoApi->getVersion();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Tries to detect the Contao 4/3/2 version by analyzing the filesystem.
     *
     * @return null|string
     */
    private function getContaoVersion()
    {
        $filesystem = $this->get('filesystem');

        if ($filesystem->exists($this->processFactory->getContaoConsolePath())) {
            return $this->contaoConsole->getVersion();
        }

        /** @var ApiKernel $kernel */
        $kernel = $this->get('kernel');

        // Required for Contao 2.11
        define('TL_ROOT', $kernel->getContaoDir());

        $files = [
            $kernel->getContaoDir().'/system/constants.php',
            $kernel->getContaoDir().'/system/config/constants.php',
        ];

        // Test if the Phar was placed in the Contao 2/3 root
        if ('' !== ($phar = \Phar::running(false))) {
            $files[] = dirname($phar).'/system/constants.php';
            $files[] = dirname($phar).'/system/config/constants.php';
        }

        $logger = $this->get('logger');
        if ($logger instanceof LoggerInterface) {
            $logger->info('Searching for Contao 2/3', ['files' => $files]);
        }

        foreach ($files as $file) {
            if ($filesystem->exists($file)) {
                try {
                    @include $file;
                } catch (\Error $e) {
                    // do nothing on error in PHP 7 or Symfony Polyfill
                } catch (\Exception $e) {
                    // do nothing on exception
                }

                if (defined('VERSION') && defined('BUILD')) {
                    return VERSION.'.'.BUILD;
                }

                break;
            }
        }

        return null;
    }
}
