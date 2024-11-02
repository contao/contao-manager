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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class OpcacheController
{
    #[Route(path: '/server/opcache', methods: ['GET'])]
    #[IsGranted('ROLE_READ')]
    public function getOpcache(): Response
    {
        if (!\function_exists('opcache_reset')) {
            return new JsonResponse(null, Response::HTTP_NOT_IMPLEMENTED);
        }

        global $opcacheEnabled;

        $status = [
            'opcache_enabled' => $opcacheEnabled,
            'reset_token' => md5(\Phar::running(false)),
        ];

        return new JsonResponse($status);
    }

    #[Route(path: '/server/opcache', methods: ['DELETE'])]
    #[IsGranted('ROLE_UPDATE')]
    public function deleteOpcache(): Response
    {
        if (!\function_exists('opcache_reset')) {
            return new JsonResponse(null, Response::HTTP_NOT_IMPLEMENTED);
        }

        opcache_reset();

        return $this->getOpcache();
    }
}
