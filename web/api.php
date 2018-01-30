<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2018 Contao Association
 *
 * @license LGPL-3.0+
 */

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Symfony\Component\HttpFoundation\Request;

require __DIR__.'/../vendor/autoload.php';

try {
    $kernel = new ApiKernel('@symfony_env@' === 'prod' ? 'prod' : 'dev');

    $request = Request::createFromGlobals();
    $response = $kernel->handle($request);
    $response->send();
    $kernel->terminate($request, $response);
} catch (\Exception $e) {
    $response = ApiProblemResponse::createFromException($e, '@symfony_env@' !== 'prod');
    $response->send();
}
