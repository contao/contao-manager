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
use Contao\ManagerApi\Config\ComposerConfig;
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
use Tenside\Core\Task\TaskList;

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
    private $managerConfig;

    /**
     * @var AuthConfig
     */
    private $authConfig;

    /**
     * @var UserConfig
     */
    private $userConfig;

    /**
     * @var ComposerConfig
     */
    private $composerConfig;

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
     * @var TaskList
     */
    private $tasks;

    /**
     * Constructor.
     *
     * @param ApiKernel             $kernel
     * @param ManagerConfig         $managerConfig
     * @param AuthConfig            $authConfig
     * @param UserConfig            $userConfig
     * @param ComposerConfig        $composerConfig
     * @param ConsoleProcessFactory $processFactory
     * @param ContaoApi             $contaoApi
     * @param TaskList              $tasks
     * @param Filesystem|null       $filesystem
     *
     * @internal param InstallationStatusDeterminator $status
     */
    public function __construct(
        ApiKernel $kernel,
        ManagerConfig $managerConfig,
        AuthConfig $authConfig,
        UserConfig $userConfig,
        ComposerConfig $composerConfig,
        ConsoleProcessFactory $processFactory,
        ContaoApi $contaoApi,
        TaskList $tasks,
        Filesystem $filesystem = null
    ) {
        $this->kernel = $kernel;
        $this->managerConfig = $managerConfig;
        $this->authConfig = $authConfig;
        $this->userConfig = $userConfig;
        $this->composerConfig = $composerConfig;
        $this->processFactory = $processFactory;
        $this->contaoApi = $contaoApi;
        $this->tasks = $tasks;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    /**
     * @return Response
     */
    public function __invoke()
    {
        if (0 !== $this->userConfig->count() && !$this->isGranted('IS_AUTHENTICATED_FULLY')) {
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
        $this->composerConfig->initialize();

        $version = $this->contaoApi->getContaoVersion();
        $status = self::STATUS_OK;

        if (0 === $this->userConfig->count() || !$this->managerConfig->has('php_cli') || !$version) {
            $status = self::STATUS_INSTALL;
        }

        $config = $this->managerConfig->all();
        $config['github_oauth_token'] = $this->authConfig->getGithubToken();

        return new JsonResponse(
            [
                'status' => $status,
                'username' => (string) $this->getUser(),
                'config' => $config,
                'version' => $version,
                'tasks' => $this->tasks->getIds(),
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

        if ($this->managerConfig->get('php_cli')) {
            $process = $this->processFactory->createManagerConsoleProcess(
                [
                    'contao-manager:check',
                    '--format=json',
                ]
            );
            $process->run();

            if (!$process->isSuccessful()) {
                return new ApiProblemResponse(ApiProblem::fromJson($process->getOutput()));
            }
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
