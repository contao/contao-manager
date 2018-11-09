<?php

namespace Contao\ManagerApi\Controller\Config;

use Contao\ManagerApi\Config\AuthConfig;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AuthController extends AbstractConfigController
{
    public function __construct(AuthConfig $config)
    {
        parent::__construct($config);
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
