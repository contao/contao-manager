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

use Contao\ManagerApi\Config\AuthConfig;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractConfigController
{
    public function __construct(AuthConfig $config)
    {
        parent::__construct($config);
    }

    #[\Symfony\Component\Routing\Attribute\Route(path: '/config/auth', methods: ['GET', 'PUT', 'PATCH'])]
    public function __invoke(Request $request): Response
    {
        return parent::__invoke($request);
    }

    #[\Symfony\Component\Routing\Attribute\Route(path: '/config/auth/github-oauth', methods: ['PUT'])]
    public function putGithubToken(Request $request): Response
    {
        if (!$this->config instanceof AuthConfig || !$request->request->has('token')) {
            throw new BadRequestHttpException('GitHub token could not be stored.');
        }

        $this->config->setGithubToken($request->request->get('token'));

        return new JsonResponse($this->config->get('github-oauth'));
    }
}
