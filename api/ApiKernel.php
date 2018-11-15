<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi;

use Composer\Util\ErrorHandler;
use Contao\ManagerApi\IntegrityCheck\CliIntegrityCheckInterface;
use Contao\ManagerApi\IntegrityCheck\WebIntegrityCheckInterface;
use Contao\ManagerApi\Task\TaskInterface;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

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
        $debug = $environment === 'dev';

        ErrorHandler::register();

        error_reporting($debug ? E_ALL : E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR);
        ini_set('display_errors', $debug);
        ini_set('error_log', $this->getLogDir().DIRECTORY_SEPARATOR.'error.log');

        parent::__construct($environment, $debug);

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
        // @see https://getcomposer.org/doc/03-cli.md#composer
        if (false !== ($composer = getenv('COMPOSER'))) {
            return dirname($composer);
        }

        if ('' !== ($phar = \Phar::running(false))) {
            $current = dirname($phar);

            if ('web' === basename($current)) {
                return dirname($current);
            }

            return $current;
        }

        $testDir = dirname(__DIR__).DIRECTORY_SEPARATOR.'test-dir';
        (new Filesystem())->mkdir($testDir);

        return $testDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        return $this->debug ? $this->getConfigDir().'/appcache' : __DIR__.'/Resources/cache';
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
        return $this->getConfigDir().'/logs';
    }

    /**
     * Gets the directory where to place manager files like config and logs.
     *
     * @return string
     */
    public function getConfigDir()
    {
        return $this->getProjectDir().DIRECTORY_SEPARATOR.'contao-manager';
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
     * {@inheritdoc}
     */
    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $routes->import(__DIR__.'/Controller', '/api', 'annotation');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/Resources/config/config_'.$c->getParameter('kernel.environment').'.yml');

        $c->registerForAutoconfiguration(WebIntegrityCheckInterface::class)
            ->addTag('app.integrity.web')
        ;

        $c->registerForAutoconfiguration(CliIntegrityCheckInterface::class)
          ->addTag('app.integrity.cli')
        ;

        $c->registerForAutoconfiguration(TaskInterface::class)
            ->addTag('monolog.logger', ['channel' => 'tasks'])
            ->addTag('app.task')
        ;
    }

    /**
     * Configures the Composer environment variables to match the current setup.
     */
    private function configureComposerEnvironment()
    {
        $root = $this->getProjectDir();

        putenv('COMPOSER='.$root.DIRECTORY_SEPARATOR.'composer.json');
        putenv('COMPOSER_HOME='.$this->getConfigDir());
        putenv('COMPOSER_HTACCESS_PROTECT=0');

        chdir($root);
    }
}
