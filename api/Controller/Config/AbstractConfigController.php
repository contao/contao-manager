<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller\Config;

use Contao\ManagerApi\Config\AbstractConfig;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractConfigController extends Controller
{
    /**
     * @var AbstractConfig
     */
    protected $config;

    /**
     * Constructor.
     *
     * @param AbstractConfig $config
     */
    public function __construct(AbstractConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function __invoke(Request $request)
    {
        switch ($request->getMethod()) {
            case 'PUT':
                $this->config->replace($request->request->all());
                break;

            case 'PATCH':
                $this->config->add($request->request->all());
                break;
        }

        return new JsonResponse($this->config->all());
    }
}
