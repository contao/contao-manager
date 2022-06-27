<?php

error_reporting(-1);

if (function_exists('ini_set')) {
    @ini_set('display_errors', 1);
    @ini_set('display_startup_errors', 1);
    @ini_set('opcache.enable_cli', '0');

    if (isset($_GET['opcache_reset']) && $_GET['opcache_reset'] === md5(Phar::running(false))) {
        $GLOBALS['opcacheEnabled'] = @ini_get('opcache.enable');
    } else {
        $GLOBALS['opcacheEnabled'] = @ini_set('opcache.enable', '0');
    }
}

if (PHP_VERSION_ID < 50509) {
    die('You are using PHP '.PHP_VERSION." but you need least PHP 5.5.9 to run the Contao Manager.\n");
}

if (!extension_loaded('Phar')) {
    die('The PHP Phar extension is not enabled.');
}

if (PHP_VERSION_ID < 70103) {
    Phar::mapPhar('contao-manager.phar');
    @include 'phar://contao-manager.phar/downgrade.php';
    die('<script>setTimeout(function() { window.location.reload(true) }, 1000)</script>');
}

if (function_exists('date_default_timezone_set') && function_exists('date_default_timezone_get')) {
    date_default_timezone_set(@date_default_timezone_get());
}

if ('cli' === PHP_SAPI || !isset($_SERVER['REQUEST_URI'])) {
    if (isset($_SERVER['argv'][1]) && 'test' === $_SERVER['argv'][1]) {
        die(json_encode(['version' => PHP_VERSION, 'version_id' => PHP_VERSION_ID, 'sapi' => PHP_SAPI]));
    }

    Phar::mapPhar('contao-manager.phar');
    require 'phar://contao-manager.phar/api/console';
} else {
    function rewrites()
    {
        // The function argument is unreliable across servers, Nginx for example is always empty
        list(,$url) = explode(basename(__FILE__), $_SERVER['REQUEST_URI'], 2);

        if (strpos($url, '..')) {
            return false;
        }

        if ('' === $url) {
            header('Location: /'.basename(__FILE__).'/');
            exit;
        }

        if (0 === strpos($url, '/api/')) {
            return '/dist/api.php'.$url;
        }

        if (!empty($url) && is_file('phar://'.__FILE__.'/dist'.$url)) {
            return '/dist'.$url;
        }

        return '/dist/index.html';
    }

    Phar::webPhar(
        null,
        'index.html',
        null,
        array(
            'log' => 'text/plain',
            'txt' => 'text/plain',
            'php' => Phar::PHP, // parse as PHP
            'css' => 'text/css',
            'gif' => 'image/gif',
            'html' => 'text/html',
            'ico' => 'image/x-ico',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'js' => 'application/x-javascript',
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            'json' => 'application/json'
        ),
        'rewrites'
    );
}

__HALT_COMPILER();
