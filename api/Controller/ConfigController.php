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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    public function patchAction(Request $request)
    {
        $this->config->add($request->request->all());

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
