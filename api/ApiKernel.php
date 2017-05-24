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
use Symfony\Component\Routing\RouteCollectionBuilder;
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
    private $contaoDir;

    /**
     * {@inheritdoc}
     */
    public function __construct($environment, $debug)
    {
        parent::__construct($environment, $debug);

        ini_set('error_log', $this->getLogDir().DIRECTORY_SEPARATOR.'api-'.date('Y-m-d').'.log');

        $this->configureComposerEnvironment();
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
            new SecurityBundle(),

            new MonologBundle(),
            new TensideCoreBundle(),
        ];
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
