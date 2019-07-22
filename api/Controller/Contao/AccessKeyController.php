<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller\Contao;

use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Contao\ManagerApi\Process\ContaoApi;
use Crell\ApiProblem\ApiProblem;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/contao/access-key", methods={"GET", "PUT", "DELETE"})
 */
class AccessKeyController extends Controller
{
    /**
     * @var ContaoApi
     */
    private $api;

    public function __construct(ContaoApi $api)
    {
        $this->api = $api;
    }

    /**
     * Handles the controller action.
     *
     * @throws \Seld\JsonLint\ParsingException
     */
    public function __invoke(Request $request): Response
    {
        switch ($request->getMethod()) {
            case 'GET':
                return $this->getAccessKey();

            case 'PUT':
                return $this->setAccessKey($request);

            case 'DELETE':
                return $this->removeAccessKey();
        }

        return new Response(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * @throws \Seld\JsonLint\ParsingException
     */
    private function getAccessKey(): Response
    {
        if (!$this->isSupported('get')) {
            return new ApiProblemResponse(
                (new ApiProblem('Contao does not support the access-key API.'))
                    ->setStatus(Response::HTTP_NOT_IMPLEMENTED)
            );
        }

        return new JsonResponse(['access-key' => $this->api->runCommand($this->getAccessKeyArguments('get'))]);
    }

    /**
     * @throws \Seld\JsonLint\ParsingException
     */
    private function setAccessKey(Request $request): Response
    {
        if (!$this->isSupported('set')) {
            return new Response(null, Response::HTTP_NOT_IMPLEMENTED);
        }

        if (!$request->request->has('user') || !$request->request->has('password')) {
            return new Response(null, Response::HTTP_BAD_REQUEST);
        }

        $user = $request->request->get('user');
        $password = $request->request->get('password');

        $accessKey = password_hash(
            $user.':'.$password,
            PASSWORD_DEFAULT
        );

        $this->api->runCommand(array_merge($this->getAccessKeyArguments('set'), [$accessKey]));

        return new JsonResponse(['access-key' => $accessKey]);
    }

    /**
     * @throws \Seld\JsonLint\ParsingException
     */
    private function removeAccessKey(): Response
    {
        if (!$this->isSupported('remove')) {
            return new Response(null, Response::HTTP_NOT_IMPLEMENTED);
        }

        $this->api->runCommand($this->getAccessKeyArguments('remove'));

        return new JsonResponse(['access-key' => '']);
    }

    private function getAccessKeyArguments(string $action): array
    {
        if (1 === $this->api->getVersion()) {
            return ['access-key:'.$action];
        }

        return ['dot-env:'.$action, 'APP_DEV_ACCESSKEY'];
    }

    /**
     * Returns whether access key command is supported.
     */
    private function isSupported(string $action): bool
    {
        return 1 === $this->api->getVersion()
            || ($this->api->hasCommand('dot-env:'.$action)
                && \in_array('APP_DEV_ACCESSKEY', $this->api->getFeatures()['contao/manager-bundle']['dot-env'], true)
            );
    }
}
