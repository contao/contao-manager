<?php

error_reporting(-1);

if (function_exists('ini_set')) {
    @ini_set('display_errors', 1);
    @ini_set('display_startup_errors', 1);
}

if (version_compare(phpversion(), '5.5.9', '<')) {
    die('You need at least PHP 5.5.9 to run the Contao Manager.');
}

if (!extension_loaded('Phar')) {
    die('The PHP Phar extension is not enabled.');
}

if (function_exists('ioncube_loader_iversion') && ioncube_loader_iversion() < 40009) {
    die('The PHP ionCube Loader extension prior to version 4.0.9 cannot handle .phar files.');
}

if (false !== ($suhosin = ini_get('suhosin.executor.include.whitelist'))) {
    $allowed = array_map('trim', explode(',', $suhosin));

    if (!in_array('phar', $allowed) && !in_array('phar://', $allowed)) {
        die('The Suhosin extension does not allow to run .phar files.');
    }
}

$isDisabled = function ($value) {
    return '' === $value || 0 === (int) $value || 'Off' === $value;
};

if (false !== ($multibyte = ini_get('zend.multibyte')) && !$isDisabled($multibyte)) {
    $unicode = ini_get(version_compare(phpversion(), '5.4', '<') ? 'detect_unicode' : 'zend.detect_unicode');

    if (!$isDisabled($unicode)) {
        die('The detect_unicode setting needs to be disabled in your php.ini.');
    }
}

unset($isDisabled, $multibyte, $unicode);

if ('cgi-fcgi' === php_sapi_name() && extension_loaded('eaccelerator') && ini_get('eaccelerator.enable')) {
    die('The PHP eAccelerator extension cannot handle .phar files.');
}

if ('cli' === PHP_SAPI) {
    Phar::mapPhar('contao-manager.phar');
    /** @noinspection UntrustedInclusionInspection */
    /** @noinspection PhpIncludeInspection */
    require 'phar://contao-manager.phar/api/console';
} else {
    Phar::webPhar(
        'contao-manager.phar',
        'index.html',
        null,
        [],
        function () {
            // The function argument is unreliable across servers, Nginx for example is always empty
            list(,$url) = explode(basename(__FILE__), $_SERVER['REQUEST_URI'], 2);

            if (strpos($url, '..')) {
                return false;
            }

            if ('' === $url) {
                header('Location: /'.basename(__FILE__).'/');
                exit;
            }

            if ('/' === $url) {
                return '/web/index.html';
            }

            if (0 === strpos($url, '/api/')) {
                return '/web/api.php'.$url;
            }

            // rewrite everything to the public folder
            return '/web'.$url;
        }
    );
}

__HALT_COMPILER();
