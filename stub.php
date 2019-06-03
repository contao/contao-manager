<?php

error_reporting(-1);

if (function_exists('ini_set')) {
    @ini_set('display_errors', 1);
    @ini_set('display_startup_errors', 1);
    @ini_set('opcache.enable', '0');
    @ini_set('opcache.enable_cli', '0');
}

if (PHP_VERSION_ID < 70103) {
    die('You are using PHP '.phpversion().' but you need least PHP 5.5.9 to run the Contao Manager.');
}

if (!extension_loaded('Phar')) {
    die('The PHP Phar extension is not enabled.');
}

if (false !== ($suhosin = ini_get('suhosin.executor.include.whitelist'))) {
    $allowed = array_map('trim', explode(',', $suhosin));

    if (!in_array('phar', $allowed) && !in_array('phar://', $allowed)) {
        die('The Suhosin extension does not allow to run .phar files.');
    }
}

if (false !== ($multibyte = ini_get('zend.multibyte')) && '' !== $multibyte && 0 !== (int) $multibyte && 'Off' !== $multibyte) {
    $unicode = ini_get('zend.detect_unicode');

    if ('' !== $unicode && 0 !== (int) $unicode && 'Off' !== $unicode) {
        die('The detect_unicode setting needs to be disabled in your php.ini.');
    }
}

unset($multibyte, $unicode);

if (function_exists('date_default_timezone_set') && function_exists('date_default_timezone_get')) {
    /** @noinspection UsageOfSilenceOperatorInspection */
    date_default_timezone_set(@date_default_timezone_get());
}

if ('cli' === PHP_SAPI || !isset($_SERVER['REQUEST_URI'])) {
    Phar::mapPhar('contao-manager.phar');
    /** @noinspection PhpIncludeInspection */
    require 'phar://contao-manager.phar/api/console';
} else {
    function rewrites() {
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
