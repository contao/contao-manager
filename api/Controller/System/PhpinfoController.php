<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Controller\System;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class PhpinfoController extends Controller
{
    /**
     * Gets response with phpinfo().
     *
     * @return Response
     */
    public function __invoke()
    {
        ob_start();
        phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES | INFO_ENVIRONMENT);

        return new Response(ob_get_clean());
    }
}
