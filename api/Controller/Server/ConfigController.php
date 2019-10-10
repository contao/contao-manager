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

use Composer\Json\JsonFile;
use Contao\ManagerApi\Composer\Environment;
use Contao\ManagerApi\Config\ManagerConfig;
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\System\ServerInfo;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/server/config", methods={"GET", "PUT"})
 */
class ConfigController
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
     * @var Environment
     */
    private $environment;

    /**
     * @var Translator
     */
    private $translator;

    public function __construct(ManagerConfig $config, ServerInfo $serverInfo, Environment $environment, Translator $translator)
    {
        $this->config = $config;
        $this->serverInfo = $serverInfo;
        $this->environment = $environment;
        $this->translator = $translator;
    }

    public function __invoke(Request $request): Response
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

    private function getTestResult(): Response
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
                $cli = $this->serverInfo->getPhpExecutableFinder()->find();
            }
        }

        return new JsonResponse(
            [
                'server' => (string) $server,
                'php_cli' => (string) $cli,
                'detected' => $detected,
                'cloud' => $this->getCloudConfig(),
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
    private function validateHostingConfig($server, $phpCli): ?ApiProblem
    {
        $errors = [];

        if ('custom' !== $server && !\array_key_exists($server, $this->serverInfo->getConfigs())) {
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

    private function validatePhpCli($phpCli, array &$errors): void
    {
        $info = $this->serverInfo->getPhpExecutableFinder()->getServerInfo($phpCli);

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

    private function getCloudConfig(): array
    {
        $issues = $this->checkCloudIssues();

        return [
            'enabled' => !$this->config->get('disable_cloud', false),
            'issues' => $issues,
        ];
    }

    private function checkCloudIssues(): array
    {
        $json = new JsonFile($this->environment->getJsonFile());

        if (!$json->exists()) {
            return [];
        }

        try {
            $data = $json->read();
        } catch (\RuntimeException $e) {
            return [$e->getMessage()];
        }

        $issues = [];

        if (isset($data['config']['platform'])) {
            $issues[] = $this->translator->trans('config.cloud.platform');
        }

        if (isset($data['config']['cache-dir'])
            || isset($data['config']['cache-files-dir'])
            || isset($data['config']['cache-repo-dir'])
            || isset($data['config']['cache-vcs-dir'])
            || isset($data['config']['cache-files-ttl'])
            || isset($data['config']['cache-files-maxsize'])
        ) {
            $issues[] = $this->translator->trans('config.cloud.cache');
        }

        return array_unique($issues);
    }
}
