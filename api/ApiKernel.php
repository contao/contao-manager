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
    private $projectDir;

    /**
     * @var string
     */
    private $configDir;

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
        if (null === $this->projectDir) {
            $this->projectDir = $this->findProjectDir();
        }

        return $this->projectDir;
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
        if (null !== $this->configDir) {
            return $this->configDir;
        }

        $this->configDir = $this->getProjectDir().DIRECTORY_SEPARATOR.'contao-manager';

        if ('' === ($phar = \Phar::running(false))) {
            return $this->configDir;
        }

        $filesystem = new Filesystem();

        // Try to find a config directory in the parent from previous version
        if (!$filesystem->exists($this->configDir)) {
            if ('web' !== basename(dirname($phar))) {
                $parentDir = dirname($this->getProjectDir()).DIRECTORY_SEPARATOR.'contao-manager';

                if ($filesystem->exists($parentDir)) {
                    $filesystem->mirror($parentDir, $this->configDir);
                }
            }

            $filesystem->mkdir($this->configDir);
        }

        // Make sure the config directory contains a .htaccess file
        if (!$filesystem->exists($this->configDir.DIRECTORY_SEPARATOR.'.htaccess')) {
            $filesystem->dumpFile($this->configDir.DIRECTORY_SEPARATOR.'.htaccess', <<<'CODE'
<IfModule !mod_authz_core.c>
    Order deny,allow
    Deny from all
</IfModule>
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
CODE
            );
        }

        return $this->configDir;
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

    /**
     * Finds the Contao installation directory depending on the Phar file or development mode.
     *
     * @return string
     */
    private function findProjectDir()
    {
        // @see https://getcomposer.org/doc/03-cli.md#composer
        if (false !== ($composer = getenv('COMPOSER'))) {
            return dirname($composer);
        }

        if ('' !== ($phar = \Phar::running(false))) {
            $current = dirname($phar);

            if ('web' === basename($current)) {

                $filesystem = new Filesystem();
                $contaoFiles = [
                    '/vendor/contao/manager-bundle/bin/contao-console',
                    '/system/constants.php',
                    '/system/config/constants.php',
                ];

                // Use current folder if it looks like Contao
                foreach ($contaoFiles as $file) {
                    if ($filesystem->exists($current.$file)) {
                        return $current;
                    }
                }

                return dirname($current);
            }

            return $current;
        }

        $testDir = dirname(__DIR__).DIRECTORY_SEPARATOR.'test-dir';
        (new Filesystem())->mkdir($testDir);

        return $testDir;
    }

    public function __serialize()
    {
        return unserialize($this->serialize());
    }

    public function __unserialize(array $data)
    {
        $this->unserialize(serialize($data));
    }
}
