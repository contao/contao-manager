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

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/server/phpinfo', methods: ['GET'])]
class PhpinfoController
{
    /**
     * Gets response with phpinfo().
     */
    public function __invoke(): Response
    {
        ob_start();
        phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES | INFO_ENVIRONMENT);

        return new Response(ob_get_clean());
    }
}
