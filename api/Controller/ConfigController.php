<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Controller;

use Contao\ManagerApi\Config\AbstractConfig;
use Contao\ManagerApi\Config\AuthConfig;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ConfigController extends Controller
{
    /**
     * @var AbstractConfig
     */
    private $config;

    /**
     * Constructor.
     *
     * @param AbstractConfig $config
     */
    public function __construct(AbstractConfig $config)
    {
        $this->config = $config;
    }

    public function getAction()
    {
        return new JsonResponse($this->config->all());
    }

    public function putAction(Request $request)
    {
        $this->config->replace($request->request->all());

        return new JsonResponse($this->config->all());
    }

    public function patchAction(Request $request)
    {
        $this->config->add($request->request->all());

        return new JsonResponse($this->config->all());
    }

    public function putGithubToken(Request $request)
    {
        if (!$this->config instanceof AuthConfig || !$request->request->has('token')) {
            throw new BadRequestHttpException('GitHub token could not be stored.');
        }

        $this->config->setGithubToken($request->request->get('token'));

        return new JsonResponse($this->config->get('github-oauth'));
    }
}
