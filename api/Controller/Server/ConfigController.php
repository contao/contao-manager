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
            $phpCli = $request->request->get('php_cli');

            if (null !== ($error = $this->validatePhpCli($phpCli))) {
                $problem = (new ApiProblem('Bad Request'))->setStatus(400);
                $problem->setDetail($error);

                return new ApiProblemResponse($problem);
            }

            $this->config->set('php_cli', $phpCli);

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
        return new JsonResponse(
            [
                'php_cli' => (string) $this->serverInfo->getPhpExecutable(),
                'cloud' => $this->getCloudConfig(),
            ]
        );
    }

    private function validatePhpCli($phpCli): ?string
    {
        $info = $this->serverInfo->getPhpExecutableFinder()->getServerInfo($phpCli);

        if (null === $info) {
            return $this->translator->trans('config.php_cli.not_found');
        }

        $vWeb = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
        $vCli = vsprintf('%s.%s', explode('.', $info['version']));

        if (version_compare($vWeb, $vCli, '<>')) {
            return $this->translator->trans(
                'config.php_cli.incompatible',
                ['cli' => $vCli, 'web' => $vWeb]
            );
        }

        return null;
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

        if (
            isset($data['config']['cache-dir'])
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
