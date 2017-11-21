<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Controller\System;

use Composer\Factory;
use Composer\IO\NullIO;
use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Crell\ApiProblem\ApiProblem;
use Seld\JsonLint\ParsingException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class ComposerController extends Controller
{
    /**
     * @var ApiKernel
     */
    private $kernel;

    /**
     * @va string
     */
    private $jsonFile;

    /**
     * @var string
     */
    private $lockFile;

    /**
     * Constructor.
     *
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        $this->jsonFile = $this->kernel->getContaoDir().'/composer.json';
        $this->lockFile = $this->kernel->getContaoDir().'/composer.lock';
    }

    /**
     * Gets response about Composer configuration and file validation.
     *
     * @return Response
     */
    public function __invoke()
    {
        if (!$this->get('contao_manager.config.manager')->has('server')
            || !$this->get('contao_manager.system.server_info')->getPhpExecutable()
        ) {
            return new ApiProblemResponse(
                (new ApiProblem('Missing hosting configuration.', '/api/server/config'))
                    ->setStatus(Response::HTTP_SERVICE_UNAVAILABLE)
            );
        }

        $result = [
            'json' => ['found' => true, 'valid' => true, 'error' => null],
            'lock' => ['found' => false, 'fresh' => false],
        ];

        try {
            $composer = Factory::create(new NullIO(), $this->kernel->getContaoDir().'/composer.json', true);
            $locker = $composer->getLocker();

            if ($locker->isLocked()) {
                $result['lock']['found'] = true;

                if ($locker->isFresh()) {
                    $result['lock']['fresh'] = true;
                }
            }
        } catch (\InvalidArgumentException $e) {
            $result['json']['found'] = false;
            $result['json']['valid'] = false;
        } catch (ParsingException $e) {
            $result['json']['valid'] = false;
            $result['json']['error'] = [
                'message' => $e->getMessage(),
                'details' => $e->getDetails(),
            ];
        }

        return new JsonResponse($result);
    }
}
