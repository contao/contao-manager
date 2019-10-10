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

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\EventListener\LocaleListener as BaseLocaleListener;

class LocaleListener extends BaseLocaleListener
{
    public function onKernelRequest(GetResponseEvent $event): void
    {
        parent::onKernelRequest($event);

        $request = $event->getRequest();

        if ($locale = $request->getPreferredLanguage()) {
            $request->setLocale($locale);
        }
    }
}
