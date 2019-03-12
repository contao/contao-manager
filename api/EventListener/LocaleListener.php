<?php

namespace Contao\ManagerApi\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\EventListener\LocaleListener as BaseLocaleListener;

class LocaleListener extends BaseLocaleListener
{
    public function onKernelRequest(GetResponseEvent $event)
    {
        parent::onKernelRequest($event);

        $request = $event->getRequest();

        if ($locale = $request->query->get('_locale')) {
            $request->setLocale($locale);
        }
    }
}
