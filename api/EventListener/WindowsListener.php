<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\EventListener;

use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class WindowsListener
{
    /**
     * Windows platform is currently not supported.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ('\\' !== DIRECTORY_SEPARATOR) {
            return;
        }

        $problem = new ApiProblem(
            'This version of Contao Manager is currently not supported on Windows.',
            'https://github.com/contao/contao-manager/issues/66'
        );

        $problem->setStatus(ApiProblemResponse::HTTP_NOT_IMPLEMENTED);

        $event->setResponse(new ApiProblemResponse($problem));
    }
}
