<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi;

use Contao\ManagerApi\Tenside\HomePathDeterminator;
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
     * @var HomePathDeterminator
     */
    private $home;

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
        return __DIR__.'/Resources/cache';
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
        return $this->getHome()->tensideDataDir().'/logs';
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
     * {@inheritdoc}
     */
    protected function initializeContainer()
    {
        parent::initializeContainer();

        $this->container->set('tenside.home', $this->getHome());
    }

    /**
     * Initializes and returns the HomePathDeterminator.
     *
     * @return HomePathDeterminator
     */
    private function getHome()
    {
        if (null === $this->home) {
            return new HomePathDeterminator();
        }

        return $this->home;
    }
}
