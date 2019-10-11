<?php

declare(strict_types=1);

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
use Terminal42\ServiceAnnotationBundle\Terminal42ServiceAnnotationBundle;

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
        $debug = 'dev' === $environment;

        ErrorHandler::register();

        error_reporting($debug ? E_ALL : E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('error_log', $this->getLogDir().\DIRECTORY_SEPARATOR.'error.log');

        parent::__construct($environment, $debug);

        $this->configureComposerEnvironment();
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new SecurityBundle(),
            new MonologBundle(),
            new Terminal42ServiceAnnotationBundle(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRootDir(): string
    {
        return __DIR__;
    }

    /**
     * {@inheritdoc}
     */
    public function getProjectDir(): string
    {
        if (null === $this->projectDir) {
            $this->projectDir = $this->findProjectDir();
        }

        return $this->projectDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir(): string
    {
        return $this->debug ? $this->getConfigDir().'/appcache' : __DIR__.'/Resources/cache';
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir(): string
    {
        return $this->getConfigDir().'/logs';
    }

    /**
     * Gets the directory where to place manager files like config and logs.
     */
    public function getConfigDir(): string
    {
        if (null !== $this->configDir) {
            return $this->configDir;
        }

        $this->configDir = $this->getProjectDir().\DIRECTORY_SEPARATOR.'contao-manager';

        if ('' === ($phar = \Phar::running(false))) {
            return $this->configDir;
        }

        $filesystem = new Filesystem();

        // Try to find a config directory in the parent from previous version
        if (!$filesystem->exists($this->configDir)) {
            if ('web' !== basename(\dirname($phar))) {
                $parentDir = \dirname($this->getProjectDir()).\DIRECTORY_SEPARATOR.'contao-manager';

                if ($filesystem->exists($parentDir)) {
                    $filesystem->mirror($parentDir, $this->configDir);
                }
            }

            $filesystem->mkdir($this->configDir);
        }

        // Make sure the config directory contains a .htaccess file
        if (!$filesystem->exists($this->configDir.\DIRECTORY_SEPARATOR.'.htaccess')) {
            $filesystem->dumpFile($this->configDir.\DIRECTORY_SEPARATOR.'.htaccess', <<<'CODE'
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
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Config\Exception\LoaderLoadException
     */
    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $routes->import(__DIR__.'/Controller', '/api', 'annotation');
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
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
    private function configureComposerEnvironment(): void
    {
        $root = $this->getProjectDir();

        putenv('COMPOSER='.$root.\DIRECTORY_SEPARATOR.'composer.json');
        putenv('COMPOSER_HOME='.$this->getConfigDir());
        putenv('COMPOSER_HTACCESS_PROTECT=0');

        chdir($root);
    }

    /**
     * Finds the Contao installation directory depending on the Phar file or development mode.
     */
    private function findProjectDir(): string
    {
        // @see https://getcomposer.org/doc/03-cli.md#composer
        if (false !== ($composer = getenv('COMPOSER'))) {
            return \dirname($composer);
        }

        if ('' !== ($phar = \Phar::running(false))) {
            if (('cli' === \PHP_SAPI || !isset($_SERVER['REQUEST_URI'])) && !empty($_SERVER['PWD'])) {
                return $_SERVER['PWD'];
            }

            $current = getcwd();

            if (!$current) {
                $current = \dirname($phar);
            }

            if ('web' === basename($current)) {
                return \dirname($current);
            }

            return $current;
        }

        $testDir = \dirname(__DIR__).\DIRECTORY_SEPARATOR.'test-dir';
        (new Filesystem())->mkdir($testDir);

        return $testDir;
    }
}
