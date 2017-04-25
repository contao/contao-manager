<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\EventListener;

use Contao\ManagerApi\Exception\ApiProblemException;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\HttpKernel\Event\KernelEvent;

class WindowsListener
{
    /**
     * Windows platform is currently not supported.
     *
     * @param KernelEvent $event
     *
     * @throws ApiProblemException
     */
    public function onKernelRequest(KernelEvent $event)
    {
        if ('\\' !== DIRECTORY_SEPARATOR) {
            return;
        }

        $problem = new ApiProblem(
            'This version of Contao Manager is currently not supported on Windows.',
            'https://github.com/contao/contao-manager/issues/66'
        );

        throw new ApiProblemException($problem);
    }
}
