<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller\Server;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/server/opcache", methods={"GET", "DELETE"})
 */
class OpcacheController
{
    public function __invoke(Request $request): Response
    {
        if (!\function_exists('opcache_reset')) {
            return new JsonResponse(null, Response::HTTP_NOT_IMPLEMENTED);
        }

        switch ($request->getMethod()) {
            case 'GET':
                return $this->getOpcache();

            case 'DELETE':
                return $this->deleteOpcache();
        }

        return new Response(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    private function getOpcache(): Response
    {
        global $opcacheEnabled;

        $status = [
            'opcache_enabled' => $opcacheEnabled,
            'reset_token' => md5(\Phar::running(false)),
        ];

        return new JsonResponse($status);
    }

    private function deleteOpcache(): Response
    {
        /** @noinspection PhpComposerExtensionStubsInspection */
        opcache_reset();

        return $this->getOpcache();
    }
}
