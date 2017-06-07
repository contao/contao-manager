<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\Yaml\Yaml;
use Tenside\CoreBundle\TensideCoreBundle;

/**
 * @property ContainerInterface $container
 */
class ApiKernel extends Kernel
{
    use MicroKernelTrait;

    /**
     * @var string
     */
    private $version = '@package_version@';

    /**
     * @var string
     */
    private $contaoDir;

    /**
     * {@inheritdoc}
     */
    public function __construct($environment)
    {
        parent::__construct($environment, $environment === 'dev');

        ini_set('error_log', $this->getLogDir().DIRECTORY_SEPARATOR.'api-'.date('Y-m-d').'.log');

        $this->configureComposerEnvironment();
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $bundles = [
            new FrameworkBundle(),
            new SecurityBundle(),

            new MonologBundle(),
            new TensideCoreBundle(),
        ];

        return $bundles;
    }

    /**
     * {@inheritdoc}
     */
    public function getRootDir()
    {
        return __DIR__;
    }

    /**
     * {@inheritdoc}
     */
    public function getProjectDir()
    {
        return dirname(__DIR__);
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        return $this->debug ? $this->getManagerDir().'/cache' : __DIR__.'/Resources/cache';
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
        return $this->getManagerDir().'/logs';
    }

    /**
     * Gets the directory where Contao is installed.
     *
     * @return string
     */
    public function getContaoDir()
    {
        if (null === $this->contaoDir) {
            if (false !== ($composer = getenv('COMPOSER'))) {
                // @see https://getcomposer.org/doc/03-cli.md#composer
                $this->contaoDir = dirname($composer);
            } elseif ('' !== ($phar = \Phar::running(false))) {
                $this->contaoDir = dirname(dirname($phar));
            } else {
                $this->contaoDir = dirname(__DIR__).DIRECTORY_SEPARATOR.'test-dir';
            }
        }

        return $this->contaoDir;
    }

    /**
     * Gets the directory where to place manager files like config and logs.
     *
     * @return string
     */
    public function getManagerDir()
    {
        return $this->getContaoDir().DIRECTORY_SEPARATOR.'contao-manager';
    }

    /**
     * Gets the current Contao Manager version.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Collects information about the current server.
     *
     * @return array
     */
    public function getServerInfo()
    {
        $hostname = gethostbyaddr(file_get_contents('https://api.ipify.org'));
        $provider = [];
        $version = $this->getVersion();

        if ($version === ('@'.'package_version'.'@')) {
            $git = new Process('git describe --always');
            $git->run();
            $version = trim($git->getOutput());
        }

        $offset = 0;
        $providers = Yaml::parse(file_get_contents(__DIR__.'/Resources/config/providers.yml'));

        while ($dot = strpos($hostname, '.', $offset)) {
            if (isset($providers[substr($hostname, $offset)])) {
                $provider = $providers[substr($hostname, $offset)];
                break;
            }
            $offset = $dot + 1;
        }

        $data = [
            'app' => [
                'version' => $version,
                'env' => $this->getEnvironment(),
                'debug' => $this->isDebug(),
                'cache_dir' => $this->getCacheDir(),
                'log_dir' => $this->getLogDir(),
                'contao_dir' => $this->getContaoDir(),
                'manager_dir' => $this->getManagerDir(),
            ],
            'php' => [
                'version' => PHP_VERSION,
                'version_id' => PHP_VERSION_ID,
                'sapi' => PHP_SAPI,
                'arch' => PHP_INT_SIZE * 8,
                'locale' => class_exists('Locale', false) && \Locale::getDefault() ? \Locale::getDefault() : 'n/a',
                'timezone' => date_default_timezone_get(),
            ],
            'server' => [
                'hostname' => $hostname,
                'os_name' => php_uname('s'),
                'os_version' => php_uname('r'),
                'arch' => php_uname('m'),
            ],
            'provider' => $provider,
        ];

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $routes->import(__DIR__.'/Resources/config/routing.yml');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/Resources/config/config_'.$c->getParameter('kernel.environment').'.yml');
    }

    /**
     * Configures the Composer environment variables to match the current setup.
     */
    private function configureComposerEnvironment()
    {
        $root = $this->getContaoDir();

        putenv('COMPOSER='.$root.DIRECTORY_SEPARATOR.'composer.json');
        putenv('COMPOSER_HOME='.$this->getManagerDir());

        chdir($root);
    }
}
