<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2018 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Controller\Server;

use Contao\ManagerApi\Config\ManagerConfig;
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\PhpExecutableFinder;
use Contao\ManagerApi\System\ServerInfo;
use Crell\ApiProblem\ApiProblem;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ConfigController extends Controller
{
    /**
     * @var ManagerConfig
     */
    private $config;

    /**
     * @var ServerInfo
     */
    private $serverInfo;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * Constructor.
     *
     * @param ManagerConfig $config
     * @param ServerInfo    $serverInfo
     * @param Translator    $translator
     */
    public function __construct(ManagerConfig $config, ServerInfo $serverInfo, Translator $translator)
    {
        $this->config = $config;
        $this->serverInfo = $serverInfo;
        $this->translator = $translator;
    }

    /**
     * Gets response about hosting configuration of the Contao Manager.
     *
     * @return Response
     */
    public function __invoke(Request $request)
    {
        if ($request->isMethod('PUT')) {
            $server = $request->request->get('server');
            $phpCli = $request->request->get('php_cli');

            $problem = $this->validateHostingConfig($server, $phpCli);

            if ($problem instanceof ApiProblem) {
                return new ApiProblemResponse($problem);
            }

            $this->config->set('server', $server);

            if (null === $phpCli) {
                $this->config->remove('php_cli');
            } else {
                $this->config->set('php_cli', $phpCli);
            }

            if ($request->request->get('cloud', true)) {
                $this->config->remove('disable_cloud');
            } else {
                $this->config->set('disable_cloud', true);
            }
        }

        return $this->getTestResult();
    }

    private function getTestResult()
    {
        $detected = false;
        $server = '';

        if ($this->config->has('server')) {
            $server = $this->config->get('server');
            $cli = $this->serverInfo->getPhpExecutable();
        } elseif ($this->config->has('php_cli')) {
            $detected = true;
            $cli = $this->config->get('php_cli');
        } else {
            $detected = true;
            $server = $this->serverInfo->detect();

            if ($server) {
                $cli = $this->serverInfo->getPhpExecutable();
            } else {
                $cli = (new PhpExecutableFinder())->find();
            }
        }

        return new JsonResponse(
            [
                'server' => (string) $server,
                'php_cli' => (string) $cli,
                'detected' => $detected,
                'cloud' => !$this->config->get('disable_cloud', false),
                'configs' => $this->serverInfo->getConfigs(),
            ]
        );
    }

    /**
     * Validates the server config and PHP cli.
     *
     * @param string $server
     * @param string $phpCli
     *
     * @return ApiProblem|null
     */
    private function validateHostingConfig($server, $phpCli)
    {
        $errors = [];

        if ('custom' !== $server && !array_key_exists($server, $this->serverInfo->getConfigs())) {
            $errors[] = [
                'source' => 'server',
                'message' => sprintf('Unknown server configuration "%s"', $server),
            ];
        }

        if ('custom' === $server && null === $phpCli) {
            $errors[] = [
                'source' => 'php_cli',
                'message' => 'Please set the PHP CLI path for custom configuration.',
            ];
        }

        if ('custom' !== $server && null !== $phpCli) {
            $errors[] = [
                'source' => 'php_cli',
                'message' => 'Cannot set PHP CLI path when config is not "custom".',
            ];
        }

        if (null !== $phpCli) {
            $this->validatePhpCli($phpCli, $errors);
        }

        if (empty($errors)) {
            return null;
        }

        $problem = (new ApiProblem('Bad Request'))->setStatus(400);
        $problem['validation'] = $errors;

        return $problem;
    }

    private function validatePhpCli($phpCli, array &$errors)
    {
        $info = (new PhpExecutableFinder())->getServerInfo($phpCli);

        if (null === $info) {
            $errors[] = [
                'source' => 'php_cli',
                'message' => $this->translator->trans('config.php_cli.not_found'),
            ];

            return;
        }

        $vWeb = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
        $vCli = vsprintf('%s.%s', explode('.', $info['version']));

        if (version_compare($vWeb, $vCli, '<>')) {
            $errors[] = [
                'source' => 'php_cli',
                'message' => $this->translator->trans(
                    'config.php_cli.incompatible',
                    ['cli' => $vCli, 'web' => $vWeb]
                ),
            ];
        }
    }
}
