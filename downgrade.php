<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

class ContaoManagerDowngrade
{
    public static function run()
    {
        if (isset($_SERVER['argv'][1]) && 'test' === $_SERVER['argv'][1]) {
            // Ignore test command to check different PHP binaries
            return;
        }

        if (('cli' === PHP_SAPI || !isset($_SERVER['REQUEST_URI']))
            && (!isset($_SERVER['argv'][1]) || 'downgrade' !== $_SERVER['argv'][1])
        ) {
            echo 'You are using PHP '.phpversion()." but you need least PHP 8.1.0 to run the Contao Manager.\n";
            echo 'Run "'.$_SERVER['argv'][0]." downgrade\" to downgrade to a PHP 7.2 compatible version.\n";
            exit;
        }

        $phar = Phar::running(false);
        $tempFile = $phar.'.downgrade';
        $url = 'https://download.contao.org/contao-manager/1.8/contao-manager.phar';

        $stream = @fopen($url, 'rb', false, StreamContextFactory::getContext($url));

        if (false === $stream
            || false === file_put_contents($tempFile, $stream)
            || false === rename($tempFile, $phar)
        ) {
            die('You are using PHP '.phpversion()." which is not supported by this Contao Manager. Automatic downgrade to version 1.8 was not successful.\n");
        }

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        $reload = '';
        if (!empty($_SERVER['REQUEST_URI'])) {
            $reload = '<script>setTimeout(function() { window.location.reload(true) }, 5000)</script>';
        }

        die("Contao Manager was downgraded to the latest version supported by your PHP version.\n$reload");
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

if (PHP_VERSION_ID < 80100) {
    ContaoManagerDowngrade::run();
}
