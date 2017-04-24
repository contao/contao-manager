<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\EventListener;

use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

class JsonRequestListener
{
    /**
     * Disallow everything except JSON and convert data to request content.
     *
     * @param KernelEvent $event
     *
     * @throws UnsupportedMediaTypeHttpException
     * @throws BadRequestHttpException
     */
    public function onKernelRequest(KernelEvent $event)
    {
        $request = $event->getRequest();

        $data = [];

        if (($content = $request->getContent()) !== '') {
            if ('json' !== $request->getContentType()) {
                throw new UnsupportedMediaTypeHttpException('Only JSON requests are supported.');
            }

            $data = json_decode($content, true);

            if (!is_array($data)) {
                throw new BadRequestHttpException('Invalid JSON data received.');
            }
        }

        $request->request->replace($data);
    }
}
