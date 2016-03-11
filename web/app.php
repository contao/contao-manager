<?php

/**
 * This file is part of contao/package-manager.
 *
 * (c) Christian Schiffler <c.schiffler@cyberspectrum.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    contao/package-manager
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Yanick Witschi <yanick.witschi@terminal42.ch>
 * @copyright  2015 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/contao/package-manager/blob/master/LICENSE MIT
 * @link       https://github.com/contao/package-manager
 * @filesource
 */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;

if (PHP_SAPI === 'cli') {
    echo 'Warning: This is the web interface, it should not be invoked via the CLI version of PHP.' . PHP_EOL;
}

// We do not want auto starting sessions.
if (ini_get('session.auto_start')) {
    session_destroy();
    session_write_close();
    if (ini_get('session.use_cookies')) {
        header_remove('Set-Cookie');
    }
}

// FIXME: change this.
ini_set('display_errors', 1);

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../app/AppKernel.php';

if (\Phar::running()) {
    $env   = 'phar';
    $debug = false;
} else {
    $env   = getenv('SYMFONY_ENV') ?: 'prod';
    $debug = getenv('SYMFONY_DEBUG') !== '0';
}

if ($debug) {
    Debug::enable();
}

$kernel  = new AppKernel($env, $debug);
$request = Request::createFromGlobals();

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
