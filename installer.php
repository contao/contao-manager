<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

class ContaoManagerInstaller
{
    public static function run()
    {
        $fileName = __FILE__;
        $tempFile = $fileName.'.install';
        $url = 'https://download.contao.org/contao-manager/stable/contao-manager.phar';

        $stream = @fopen($url, 'rb', false, StreamContextFactory::getContext($url));

        if (false === $stream
            || false === file_put_contents($tempFile, $stream)
            || false === rename($tempFile, $fileName)
        ) {
            self::error();
        }

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        self::success();
    }

    private static function success()
    {
        if (('cli' === PHP_SAPI || !isset($_SERVER['REQUEST_URI']))) {
            die("Contao Manager installed successfully. Please re-run the script.\n");
        }

        self::html("Installing Contao Manager …\n<script>setTimeout(function() { window.location.reload(true) }, 2000)</script>", true);
    }

    private static function error()
    {
        if (('cli' === PHP_SAPI || !isset($_SERVER['REQUEST_URI']))) {
            die("Installation of the Contao Manager has failed. https://to.contao.org/install-failed\n");
        }

        self::html('Installation of the Contao Manager has failed.<br><br> <a href="https://to.contao.org/install-failed" target="_blank" class="widget-button">Help</a>');
    }

    private static function html($message, $loading = false)
    {
        $loading = $loading ? 'animate-initializing' : '';

        die(<<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Contao Manager</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
    <style>
        :root {
            --text: #535353;
            --link: #F47C00;
            --body-bg: #EBE6DB;
            --btn: #6A8CA6;
            --btn-active: rgb(92.1785714286, 127.3928571429, 154.3214285714);
            --clr-btn: #FFF;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --text: #DDD;
                --link: #F47C00;
                --body-bg: #0F0F11;
                --btn: #25455F;
                --btn-active: #253846;
            }
        }

        html {
            box-sizing: border-box;
        }

        *, *:before, *:after {
            box-sizing: inherit;
        }

        html, body, #app {
            height: 100%;
        }

        body, p {
            margin: 0;
            padding: 0;
        }

        html, body, p, div {
            text-size-adjust: none;
        }

        body {
            background: var(--body-bg);
            overflow-y: hidden;
        }

        #app {
            overflow-y: scroll;
        }

        body, input, textarea, button {
            font: 300 14px/1.4 -apple-system, system-ui, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            color: var(--text);
        }

        strong {
            font-weight: 600;
        }

        a {
            color: var(--link);
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .widget-button {
            display: inline-block;
            height: 38px;
            padding: 0 20px;
            border: none;
            background-color: var(--btn);
            color: var(--clr-btn);
            font-weight: 600;
            font-size: 0.8em;
            line-height: 38px;
            text-decoration: none;
            text-align: center;
            white-space: nowrap;
            cursor: pointer;
            border-radius: 5px;
        }

        .widget-button:hover, .widget-button:active {
            background-color: var(--btn-active);
        }

        .widget-button--info {
            --btn: var(--btn-info);
            --btn-active: var(--btn-info-active);
        }

        .widget-button:hover {
            text-decoration: none;
        }

        h1 {
            font-size: 18px;
            line-height: 30px;
            margin-bottom: 10px;
        }

        .animate-initializing {
            animation: initializing 1s linear infinite;
        }

        @keyframes initializing {
            0% {
                opacity: 0.5;
            }
            50% {
                opacity: 1;
            }
            100% {
                opacity: 0.5;
            }
        }

        .view-init {
            display: table;
            width: 100%;
            height: 100%;
        }

        .view-init__cell {
            display: table-cell;
            font-size: 1.5em;
            text-align: center;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div id="app">
        <div class="view-init" style="height: 100%;">
            <div class="view-init__cell $loading">
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 178.6 155.9"><path fill="#fff" d="M11.8-.1C5.3-.1 0 5.2 0 11.7v132.4c0 6.5 5.3 11.8 11.8 11.8h155c6.5 0 11.8-5.2 11.8-11.7V11.7c0-6.5-5.3-11.8-11.8-11.8h-155z"/><path fill="#f47c00" d="M15.9 94.6c5 23.3 9.2 45.4 23.7 61.4H11.8C5.3 156 0 150.8 0 144.3V11.7C0 5.2 5.3-.1 11.8-.1h20.1C27 4.4 22.7 9.5 19.1 15.1 3.2 39.4 9.8 65.9 15.9 94.6zM166.8-.1h-31.5c7.5 7.5 13.8 17.1 18.5 29.1l-47.9 10.1C100.6 29.1 92.6 20.8 77 24c-8.6 1.8-14.3 6.6-16.9 11.9-3.1 6.5-4.6 13.8 2.8 48.6s11.8 40.8 17.3 45.5c4.5 3.8 11.7 5.9 20.3 4.1 15.6-3.3 19.5-14.2 20.1-25.5l47.9-10.1c1.1 24.8-6.5 44-20.1 57.3h18.2c6.5 0 11.8-5.2 11.8-11.7V11.7c.2-6.5-5.1-11.8-11.6-11.8z"/></svg>
                <p class="view-init__message">$message</p>
            </div>
        </div>
    </div>
</body>
HTML);
    }
}

/**
 * @see Composer\Util\StreamContextFactory
 */
class StreamContextFactory
{
    /**
     * Creates a context supporting HTTP proxies
     *
     * @param  string            $url            URL the context is to be used for
     * @param  array             $defaultOptions Options to merge with the default
     * @param  array             $defaultParams  Parameters to specify on the context
     * @throws \RuntimeException if https proxy required and OpenSSL uninstalled
     * @return resource          Default context
     */
    public static function getContext($url, array $defaultOptions = array(), array $defaultParams = array())
    {
        $options = array('http' => array(
            // specify defaults again to try and work better with curlwrappers enabled
            'follow_location' => 1,
            'max_redirects' => 20,
        ));

        // Handle HTTP_PROXY/http_proxy on CLI only for security reasons
        if ((PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') && (!empty($_SERVER['HTTP_PROXY']) || !empty($_SERVER['http_proxy']))) {
            $proxy = parse_url(!empty($_SERVER['http_proxy']) ? $_SERVER['http_proxy'] : $_SERVER['HTTP_PROXY']);
        }

        // Prefer CGI_HTTP_PROXY if available
        if (!empty($_SERVER['CGI_HTTP_PROXY'])) {
            $proxy = parse_url($_SERVER['CGI_HTTP_PROXY']);
        }

        // Override with HTTPS proxy if present and URL is https
        if (preg_match('{^https://}i', $url) && (!empty($_SERVER['HTTPS_PROXY']) || !empty($_SERVER['https_proxy']))) {
            $proxy = parse_url(!empty($_SERVER['https_proxy']) ? $_SERVER['https_proxy'] : $_SERVER['HTTPS_PROXY']);
        }

        // Remove proxy if URL matches no_proxy directive
        if ((!empty($_SERVER['NO_PROXY']) || !empty($_SERVER['no_proxy'])) && parse_url($url, PHP_URL_HOST)) {
            $pattern = new NoProxyPattern(!empty($_SERVER['no_proxy']) ? $_SERVER['no_proxy'] : $_SERVER['NO_PROXY']);
            if ($pattern->test($url)) {
                unset($proxy);
            }
        }

        if (!empty($proxy)) {
            $proxyURL = isset($proxy['scheme']) ? $proxy['scheme'] . '://' : '';
            $proxyURL .= isset($proxy['host']) ? $proxy['host'] : '';

            if (isset($proxy['port'])) {
                $proxyURL .= ":" . $proxy['port'];
            } elseif ('http://' === substr($proxyURL, 0, 7)) {
                $proxyURL .= ":80";
            } elseif ('https://' === substr($proxyURL, 0, 8)) {
                $proxyURL .= ":443";
            }

            // http(s):// is not supported in proxy
            $proxyURL = str_replace(array('http://', 'https://'), array('tcp://', 'ssl://'), $proxyURL);

            if (0 === strpos($proxyURL, 'ssl:') && !extension_loaded('openssl')) {
                throw new RuntimeException('You must enable the openssl extension to use a proxy over https');
            }

            $options['http']['proxy'] = $proxyURL;

            // enabled request_fulluri unless it is explicitly disabled
            switch (parse_url($url, PHP_URL_SCHEME)) {
                case 'http': // default request_fulluri to true
                    $reqFullUriEnv = getenv('HTTP_PROXY_REQUEST_FULLURI');
                    if ($reqFullUriEnv === false || $reqFullUriEnv === '' || (strtolower($reqFullUriEnv) !== 'false' && (bool) $reqFullUriEnv)) {
                        $options['http']['request_fulluri'] = true;
                    }
                    break;
                case 'https': // default request_fulluri to true
                    $reqFullUriEnv = getenv('HTTPS_PROXY_REQUEST_FULLURI');
                    if ($reqFullUriEnv === false || $reqFullUriEnv === '' || (strtolower($reqFullUriEnv) !== 'false' && (bool) $reqFullUriEnv)) {
                        $options['http']['request_fulluri'] = true;
                    }
                    break;
            }

            // add SNI opts for https URLs
            if ('https' === parse_url($url, PHP_URL_SCHEME)) {
                $options['ssl']['SNI_enabled'] = true;
                if (PHP_VERSION_ID < 50600) {
                    $options['ssl']['SNI_server_name'] = parse_url($url, PHP_URL_HOST);
                }
            }

            // handle proxy auth if present
            if (isset($proxy['user'])) {
                $auth = rawurldecode($proxy['user']);
                if (isset($proxy['pass'])) {
                    $auth .= ':' . rawurldecode($proxy['pass']);
                }
                $auth = base64_encode($auth);

                // Preserve headers if already set in default options
                if (isset($defaultOptions['http']['header'])) {
                    if (is_string($defaultOptions['http']['header'])) {
                        $defaultOptions['http']['header'] = array($defaultOptions['http']['header']);
                    }
                    $defaultOptions['http']['header'][] = "Proxy-Authorization: Basic {$auth}";
                } else {
                    $options['http']['header'] = array("Proxy-Authorization: Basic {$auth}");
                }
            }
        }

        $options = array_replace_recursive($options, $defaultOptions);

        if (isset($options['http']['header'])) {
            $options['http']['header'] = self::fixHttpHeaderField($options['http']['header']);
        }

        if (defined('HHVM_VERSION')) {
            $phpVersion = 'HHVM ' . HHVM_VERSION;
        } else {
            $phpVersion = 'PHP ' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION;
        }

        if (!isset($options['http']['header']) || false === stripos(implode('', $options['http']['header']), 'user-agent')) {
            $options['http']['header'][] = sprintf(
                'User-Agent: Contao Manager/@package_version@ (%s; %s; %s%s)',
                function_exists('php_uname') ? php_uname('s') : 'Unknown',
                function_exists('php_uname') ? php_uname('r') : 'Unknown',
                $phpVersion,
                getenv('CI') ? '; CI' : ''
            );
        }

        return stream_context_create($options, $defaultParams);
    }

    /**
     * A bug in PHP prevents the headers from correctly being sent when a content-type header is present and
     * NOT at the end of the array
     *
     * This method fixes the array by moving the content-type header to the end
     *
     * @link https://bugs.php.net/bug.php?id=61548
     * @param string|array $header
     * @return array
     */
    private static function fixHttpHeaderField($header)
    {
        if (!is_array($header)) {
            $header = explode("\r\n", $header);
        }
        uasort($header, function ($el) {
            return stripos($el, 'content-type') === 0 ? 1 : -1;
        });

        return $header;
    }
}

/**
 * @see Composer\Util\NoProxyPattern
 */
class NoProxyPattern
{
    /**
     * @var string[]
     */
    protected $rules = array();

    /**
     * @param string $pattern no_proxy pattern
     */
    public function __construct($pattern)
    {
        $this->rules = preg_split("/[\s,]+/", $pattern);
    }

    /**
     * Test a URL against the stored pattern.
     *
     * @param string $url
     *
     * @return bool true if the URL matches one of the rules.
     */
    public function test($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);

        if (empty($port)) {
            switch (parse_url($url, PHP_URL_SCHEME)) {
                case 'http':
                    $port = 80;
                    break;
                case 'https':
                    $port = 443;
                    break;
            }
        }

        foreach ($this->rules as $rule) {
            if ($rule === '*') {
                return true;
            }

            list($ruleHost) = explode(':', $rule);
            list($base) = explode('/', $ruleHost);

            if (filter_var($base, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                // ip or cidr match

                if (!isset($ip)) {
                    $ip = gethostbyname($host);
                }

                if (strpos($ruleHost, '/') === false) {
                    $match = $ip === $ruleHost;
                } else {
                    // gethostbyname() failed to resolve $host to an ip, so we assume
                    // it must be proxied to let the proxy's DNS resolve it
                    if ($ip === $host) {
                        $match = false;
                    } else {
                        // match resolved IP against the rule
                        $match = self::inCIDRBlock($ruleHost, $ip);
                    }
                }
            } else {
                // match end of domain

                $haystack = '.' . trim($host, '.') . '.';
                $needle = '.'. trim($ruleHost, '.') .'.';
                $match = stripos(strrev($haystack), strrev($needle)) === 0;
            }

            // final port check
            if ($match && strpos($rule, ':') !== false) {
                list(, $rulePort) = explode(':', $rule);
                if (!empty($rulePort) && $port != $rulePort) {
                    $match = false;
                }
            }

            if ($match) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check an IP address against a CIDR
     *
     * http://framework.zend.com/svn/framework/extras/incubator/library/ZendX/Whois/Adapter/Cidr.php
     *
     * @param string $cidr IPv4 block in CIDR notation
     * @param string $ip   IPv4 address
     *
     * @return bool
     */
    private static function inCIDRBlock($cidr, $ip)
    {
        // Get the base and the bits from the CIDR
        list($base, $bits) = explode('/', $cidr);

        // Now split it up into it's classes
        list($a, $b, $c, $d) = explode('.', $base);

        // Now do some bit shifting/switching to convert to ints
        $i = ($a << 24) + ($b << 16) + ($c << 8) + $d;
        $mask = $bits == 0 ? 0 : (~0 << (32 - $bits));

        // Here's our lowest int
        $low = $i & $mask;

        // Here's our highest int
        $high = $i | (~$mask & 0xFFFFFFFF);

        // Now split the ip we're checking against up into classes
        list($a, $b, $c, $d) = explode('.', $ip);

        // Now convert the ip we're checking against to an int
        $check = ($a << 24) + ($b << 16) + ($c << 8) + $d;

        // If the ip is within the range, including highest/lowest values,
        // then it's within the CIDR range
        return $check >= $low && $check <= $high;
    }
}

ContaoManagerInstaller::run();
