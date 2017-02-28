<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

use Contao\ManagerApi\ApiKernel;
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;

require __DIR__.'/../vendor/autoload.php';

if (\Phar::running()) {
    $env = 'prod';
    $debug = false;
} else {
    $env = getenv('SYMFONY_ENV') ?: 'dev';
    $debug = getenv('SYMFONY_DEBUG') !== '0';
}

if ($debug) {
    Debug::enable();
}

$kernel = new ApiKernel($env, $debug);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
