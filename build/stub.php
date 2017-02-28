<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
