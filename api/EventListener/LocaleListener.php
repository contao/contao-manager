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

use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\EventListener\LocaleListener as BaseLocaleListener;

#[AsDecorator('locale_listener')]
class LocaleListener implements EventSubscriberInterface
{
    public function __construct(private readonly BaseLocaleListener $inner)
    {
    }

    public function setDefaultLocale(KernelEvent $event): void
    {
        $this->inner->setDefaultLocale($event);
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $this->inner->onKernelRequest($event);

        $request = $event->getRequest();

        if ($locale = $request->getPreferredLanguage()) {
            $request->setLocale($locale);
        }
    }

    public function onKernelFinishRequest(FinishRequestEvent $event): void
    {
        $this->inner->onKernelFinishRequest($event);
    }

    public static function getSubscribedEvents(): array
    {
        return BaseLocaleListener::getSubscribedEvents();
    }
}
