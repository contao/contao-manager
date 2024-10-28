<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller\Config;

use Contao\ManagerApi\Config\AbstractConfig;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractConfigController
{
    public function __construct(protected \Contao\ManagerApi\Config\AbstractConfig $config)
    {
    }

    public function __invoke(Request $request): Response
    {
        match ($request->getMethod()) {
            'PUT' => $this->config->replace($request->request->all()),
            'PATCH' => $this->config->add($request->request->all()),
            default => new JsonResponse($this->config->all()),
        };

        return new JsonResponse($this->config->all());
    }
}
