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

use Contao\ManagerApi\Security\TotpBadge;
use Contao\ManagerApi\Security\User;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

#[AsEventListener]
class CheckTotpListener
{
    public function __invoke(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();

        if (!$passport->hasBadge(TotpBadge::class)) {
            return;
        }

        /** @var TotpBadge $badge */
        $badge = $passport->getBadge(TotpBadge::class);
        $user = $passport->getUser();

        if ($badge->isResolved() || !$user instanceof User) {
            return;
        }

        $badge->verify($user);
    }
}
