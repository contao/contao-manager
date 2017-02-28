<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AppController extends Controller
{
    public function statusAction()
    {
        $status = $this->get('tenside.status');

        if (!$status->isTensideConfigured()) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        //return new Response(null, Response::HTTP_INTERNAL_SERVER_ERROR);

        return new JsonResponse(
            [
                'status' => 'ok',
            ]
        );
    }
}
