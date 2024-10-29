<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Symfony\Component\HttpFoundation\Request;

require __DIR__.'/../vendor/autoload.php';

try {
    // @phpstan-ignore identical.alwaysFalse
    $kernel = new ApiKernel('@symfony_env@' === 'prod' ? 'prod' : 'dev');

    $request = Request::createFromGlobals();
    $response = $kernel->handle($request);
    $response->send();
    $kernel->terminate($request, $response);
} catch (Throwable $throwable) {
    ApiProblemResponse::createFromException($throwable, '@symfony_env@' !== 'prod')->send();
}
