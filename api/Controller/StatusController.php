<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Controller;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\Config\ManagerConfig;
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Contao\ManagerApi\IntegrityCheck\IntegrityCheckInterface;
use Contao\ManagerApi\Tenside\InstallationStatusDeterminator;
use Crell\ApiProblem\ApiProblem;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\PhpExecutableFinder;

class StatusController extends Controller
{
    const STATUS_NEW = 'new'; // Manager not installed
    const STATUS_AUTHENTICATE = 'auth'; // Manager installed, requires authentication
    const STATUS_EMPTY = 'empty'; // Contao not installed
    const STATUS_OK = 'ok'; // Contao is ready
    const STATUS_CONFLICT = 'conflict'; // Contao has conflict

    /**
     * @var ApiKernel
     */
    private $kernel;

    /**
     * @var ManagerConfig
     */
    private $config;

    /**
     * @var InstallationStatusDeterminator
     */
    private $status;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var IntegrityCheckInterface[]
     */
    private $checks = [];

    /**
     * Constructor.
     *
     * @param ApiKernel                      $kernel
     * @param ManagerConfig                  $config
     * @param InstallationStatusDeterminator $status
     * @param Filesystem|null                $filesystem
     */
    public function __construct(
        ApiKernel $kernel,
        ManagerConfig $config,
        InstallationStatusDeterminator $status,
        Filesystem $filesystem = null
    ) {
        $this->kernel = $kernel;
        $this->config = $config;
        $this->status = $status;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    /**
     * @return Response
     */
    public function __invoke()
    {
        $this->createHtaccess();

        if (!$this->status->hasUsers()) {
            return $this->runIntegrityChecks() ?: $this->getResponse(self::STATUS_NEW);
        }

        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->getResponse(self::STATUS_AUTHENTICATE, 401);
        }

        if (null !== ($response = $this->runIntegrityChecks())) {
            return $response;
        }

        return $this->getResponse($this->status->isComplete() ? self::STATUS_OK : self::STATUS_EMPTY);
    }

    /**
     * Adds an integrity check.
     *
     * @param IntegrityCheckInterface $check
     */
    public function addIntegrityCheck(IntegrityCheckInterface $check)
    {
        $this->checks[] = $check;
    }

    /**
     * Runs integrity checks and returns response of the first failure.
     *
     * @return ApiProblemResponse|null
     */
    private function runIntegrityChecks()
    {
        foreach ($this->checks as $check) {
            if (($problem = $check->run()) instanceof ApiProblem) {
                return new ApiProblemResponse($problem);
            }
        }

        return null;
    }

    /**
     * @param string $status
     * @param int    $code
     *
     * @return JsonResponse
     */
    private function getResponse($status, $code = 200)
    {
        $version = null;

        if ($this->status->isProjectPresent() || $this->status->isProjectInstalled()) {
            // TODO report correct Contao version
            $version = 'x.x.x';
        }

        return new JsonResponse(
            [
                'status' => $status,
                'username' => (string) $this->getUser(),
                'config' => $this->getConfig(),
                'version' => $version,
            ],
            $code
        );
    }

    /**
     * @return array
     */
    private function getConfig()
    {
        $result = $this->config->all();

        if (!isset($result['php_cli'])) {
            $result['php_cli'] = (new PhpExecutableFinder())->find(false);
        }

        if (!isset($result['php_cli_arguments'])) {
            $result['php_cli_arguments'] = (new PhpExecutableFinder())->findArguments();
        }

        return $result;
    }

    /**
     * Creates a .htaccess file in the Contao root to prevent Composer from adding it.
     */
    private function createHtaccess()
    {
        $htaccess = $this->kernel->getContaoDir().DIRECTORY_SEPARATOR.'.htaccess';

        if (!file_exists($htaccess)) {
            $this->filesystem->dumpFile(
                $htaccess,
                <<<'TAG'
# This file must be present to prevent Composer from creating it
# see https://github.com/contao/contao-manager/issues/58
TAG
            );
        }
    }
}
