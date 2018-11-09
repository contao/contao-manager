<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller\Server;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/server/opcache", methods={"GET", "DELETE"})
 */
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
        /** @noinspection PhpComposerExtensionStubsInspection */
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

        /** @noinspection PhpComposerExtensionStubsInspection */
        opcache_reset();

        return $this->getOpcache();
    }
}
