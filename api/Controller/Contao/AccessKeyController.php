<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller\Contao;

use Contao\ManagerApi\Process\ContaoApi;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AccessKeyController extends Controller
{
    /**
     * @var ContaoApi
     */
    private $api;

    /**
     * Constructor.
     *
     * @param ContaoApi $api
     */
    public function __construct(ContaoApi $api)
    {
        $this->api = $api;
    }

    /**
     * Handles the controller action.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function __invoke(Request $request)
    {
        if ($this->api->getVersion() < 1) {
            return new Response(null, Response::HTTP_NOT_IMPLEMENTED);
        }

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
     * @return JsonResponse
     */
    private function getAccessKey()
    {
        return new JsonResponse(['access-key' => $this->api->getAccessKey()]);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse|Response
     */
    private function setAccessKey(Request $request)
    {
        if (!$request->request->has('user') || !$request->request->has('password')) {
            return new Response(null, Response::HTTP_BAD_REQUEST);
        }

        $user = $request->request->get('user');
        $password = $request->request->get('password');

        $accessKey = password_hash(
            $user.':'.$password,
            PASSWORD_DEFAULT
        );

        $this->api->setAccessKey($accessKey);

        return new JsonResponse(['access-key' => $accessKey]);
    }

    /**
     * @return JsonResponse
     */
    private function removeAccessKey()
    {
        $this->api->removeAccessKey();

        return new JsonResponse(['access-key' => '']);
    }
}
