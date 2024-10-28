<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

// Priority must be lower than the router (defaults to 32)
#[AsEventListener(priority: 20)]
class JsonRequestListener
{
    /**
     * Disallow everything except JSON and convert data to request content.
     *
     * @throws UnsupportedMediaTypeHttpException
     * @throws BadRequestHttpException
     */
    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $content = $request->getContent();

        if ('' === $content && $request->attributes->get('form-data')) {
            return;
        }

        $data = [];

        if ('' !== $content) {
            if ('json' !== $request->getContentTypeFormat()) {
                throw new UnsupportedMediaTypeHttpException('Only JSON requests are supported.');
            }

            $data = json_decode($content, true);

            if (!\is_array($data)) {
                throw new BadRequestHttpException('Invalid JSON data received.');
            }
        }

        $request->request->replace($data);
    }
}
