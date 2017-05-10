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
use Contao\ManagerApi\Config\AuthConfig;
use Contao\ManagerApi\Config\ManagerConfig;
use Contao\ManagerApi\Config\UserConfig;
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Contao\ManagerApi\IntegrityCheck\IntegrityCheckInterface;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Contao\ManagerApi\Process\ContaoApi;
use Crell\ApiProblem\ApiProblem;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class StatusController extends Controller
{
    const STATUS_INSTALL = 'install';
    const STATUS_AUTHENTICATE = 'auth';
    const STATUS_OK = 'ok';

    /**
     * @var ApiKernel
     */
    private $kernel;

    /**
     * @var ManagerConfig
     */
    private $config;

    /**
     * @var AuthConfig
     */
    private $auth;

    /**
     * @var UserConfig
     */
    private $users;

    /**
     * @var ConsoleProcessFactory
     */
    private $processFactory;

    /**
     * @var ContaoApi
     */
    private $contaoApi;

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
     * @param ApiKernel             $kernel
     * @param ManagerConfig         $config
     * @param AuthConfig            $auth
     * @param UserConfig            $users
     * @param ConsoleProcessFactory $processFactory
     * @param ContaoApi             $contaoApi
     * @param Filesystem|null       $filesystem
     *
     * @internal param InstallationStatusDeterminator $status
     */
    public function __construct(
        ApiKernel $kernel,
        ManagerConfig $config,
        AuthConfig $auth,
        UserConfig $users,
        ConsoleProcessFactory $processFactory,
        ContaoApi $contaoApi,
        Filesystem $filesystem = null
    ) {
        $this->kernel = $kernel;
        $this->config = $config;
        $this->auth = $auth;
        $this->users = $users;
        $this->contaoApi = $contaoApi;
        $this->filesystem = $filesystem ?: new Filesystem();
        $this->processFactory = $processFactory;
    }

    /**
     * @return Response
     */
    public function __invoke()
    {
        if (0 !== $this->users->count() && !$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new JsonResponse(
                [
                    'status' => self::STATUS_AUTHENTICATE,
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }

        if (null !== ($response = $this->runIntegrityChecks())) {
            return $response;
        }

        $this->createHtaccess();

        $version = $this->contaoApi->getContaoVersion();

        if (0 === $this->users->count()) {
            $status = self::STATUS_INSTALL;
        } elseif ($this->config->has('php_cli') && $version) {
            $status = self::STATUS_OK;
        } else {
            $status = self::STATUS_INSTALL;
        }

        $config = $this->config->all();
        $config['github_oauth_token'] = $this->auth->getGithubToken();

        return new JsonResponse(
            [
                'status' => $status,
                'username' => (string) $this->getUser(),
                'config' => $config,
                'version' => $version,
            ]
        );
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

        $process = $this->processFactory->createManagerConsoleProcess(['contao-manager:check', '--format=json']);
        $process->run();

        if (!$process->isSuccessful()) {
            return new ApiProblemResponse(ApiProblem::fromJson($process->getOutput()));
        }

        return null;
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
