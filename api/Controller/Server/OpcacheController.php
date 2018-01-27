<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Controller\Server;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OpcacheController extends Controller
{
    /**
     * Handles the controller action.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function __invoke(Request $request)
    {
        if (!function_exists('opcache_get_status')) {
            return new Response(null, Response::HTTP_NOT_IMPLEMENTED);
        }

        switch ($request->getMethod()) {
            case 'GET':
                return $this->getOpcache();

            case 'DELETE':
                return $this->deleteOpcache();
        }

        return new Response(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * @return Response
     */
    private function getOpcache()
    {
        $status = opcache_get_status(false);

        if (false === $status) {
            $status = [
                'opcache_enabled' => false,
                'cache_full' => false,
                'restart_pending' => false,
                'restart_in_progress' => false,
                'memory_usage' => [],
                'interned_strings_usage' => [],
                'opcache_statistics' => [],
            ];
        }

        return new JsonResponse($status);
    }

    /**
     * @return Response
     */
    private function deleteOpcache()
    {
        if (!function_exists('opcache_reset')) {
            return new Response(null, Response::HTTP_NOT_IMPLEMENTED);
        }

        opcache_reset();

        return $this->getOpcache();
    }
}
