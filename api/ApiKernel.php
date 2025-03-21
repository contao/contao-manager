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
use Contao\ManagerApi\Exception\ApiProblemException;
use Contao\ManagerApi\I18n\Translator;
use Crell\ApiProblem\ApiProblem;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollection;

/**
 * @property ContainerInterface $container
 */
class ApiKernel extends Kernel
{
    public const MANAGER_VERSION = '@manager_version@';

    public const VERSION_KEY = '@manager_version'.'@';

    private string|null $projectDir = null;

    private string|null $configDir = null;

    private string|null $publicDir = null;

    private readonly Filesystem $filesystem;

    public function __construct(string $environment)
    {
        $this->filesystem = new Filesystem();

        $debug = 'dev' === $environment;

        ErrorHandler::register();

        error_reporting($debug ? E_ALL : E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('error_log', $this->getLogDir().\DIRECTORY_SEPARATOR.'error-'.date('Y-m-d').'.log');

        parent::__construct($environment, $debug);

        $this->configureComposerEnvironment();
    }

    public function isWebDir(): bool
    {
        $publicDir = $this->getPublicDir();

        return 'web' === \dirname($publicDir) && $publicDir !== $this->getProjectDir();
    }

    public function getRootDir(): string
    {
        return __DIR__;
    }

    public function getProjectDir(): string
    {
        if (null === $this->projectDir) {
            $this->findProjectDir();
        }

        return $this->projectDir;
    }

    public function getPublicDir(): string
    {
        $this->getProjectDir();

        return $this->publicDir;
    }

    public function getCacheDir(): string
    {
        $cacheDir = $this->debug ? $this->getConfigDir().'/appcache' : __DIR__.'/Resources/cache';

        $this->ensureDirectoryExists($cacheDir);

        return $cacheDir;
    }

    public function getLogDir(): string
    {
        $logDir = $this->getConfigDir().'/logs';

        $this->ensureDirectoryExists($logDir);

        return $logDir;
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

        if (!self::isPhar()) {
            return $this->configDir;
        }

        // Try to find a config directory in the parent from previous version
        if (!$this->filesystem->exists($this->configDir)) {
            $parentDir = \dirname($this->getProjectDir()).\DIRECTORY_SEPARATOR.'contao-manager';

            if ($this->filesystem->exists($parentDir.'/manager.json')) {
                $this->filesystem->mirror($parentDir, $this->configDir);
            }

            $this->ensureDirectoryExists($this->configDir);
        }

        // Make sure the config directory contains a .htaccess file
        if (!$this->filesystem->exists($this->configDir.\DIRECTORY_SEPARATOR.'.htaccess')) {
            $this->filesystem->dumpFile(
                $this->configDir.\DIRECTORY_SEPARATOR.'.htaccess',
                <<<'CODE'
                    <IfModule !mod_authz_core.c>
                        Order deny,allow
                        Deny from all
                    </IfModule>
                    <IfModule mod_authz_core.c>
                        Require all denied
                    </IfModule>
                    CODE,
            );
        }

        return $this->configDir;
    }

    public function getTranslator(): Translator
    {
        if ($this->container) {
            return $this->container->get(Translator::class);
        }

        // The kernel has not been bootet successfully, manually create a translator
        $requestStack = new RequestStack();
        $requestStack->push(Request::createFromGlobals());

        return new Translator($requestStack);
    }

    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new SecurityBundle(),
            new MonologBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__.'/Resources/config/config_'.$this->environment.'.yaml');
    }

    /**
     * Loads the routes using framework.router config. We must use a loader method not
     * e.g. a routes.yaml because of dynamic path to the Phar.
     */
    public function loadRoutes(LoaderInterface $loader): RouteCollection
    {
        $resolver = $loader->getResolver()->resolve(__DIR__.'/Controller', 'attribute');

        $routes = $resolver->load(__DIR__.'/Controller', 'attribute');
        $routes->addPrefix('api');

        return $routes;
    }

    public static function isPhar(): bool
    {
        return '' !== \Phar::running(false);
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
     * Finds the Contao installation directory depending on the Phar file or
     * development mode.
     */
    private function findProjectDir(): void
    {
        // @see https://getcomposer.org/doc/03-cli.md#composer
        if (false !== ($composer = getenv('COMPOSER'))) {
            // We don't know the public dir when running on command line, but it shouldn't matter
            $this->projectDir = \dirname($composer);
            $this->publicDir = $this->projectDir;

            return;
        }

        // Not a phar file, use test directory in local development
        if (!self::isPhar()) {
            $this->projectDir = \dirname(__DIR__).\DIRECTORY_SEPARATOR.'test-dir';
            $this->publicDir = $this->projectDir.'/web';
            $this->ensureDirectoryExists($this->publicDir);

            return;
        }

        // Use the current working directory in CLI mode
        if (('cli' === \PHP_SAPI || !isset($_SERVER['REQUEST_URI'])) && !empty($_SERVER['PWD'])) {
            // We don't know the public dir when running on command line, but it shouldn't matter
            $this->projectDir = $_SERVER['PWD'];
            $this->publicDir = $_SERVER['PWD'];

            return;
        }

        $current = getcwd();

        if (!$current) {
            $current = \dirname(\Phar::running(false));
        }

        // Always use current folder if it is not named "web" or "public"
        if ('web' !== basename($current) && 'public' !== basename($current)) {
            $this->projectDir = $current;
            $this->publicDir = $current;

            return;
        }

        $contaoFiles = [
            '/vendor/contao/manager-bundle/bin/contao-console',
            '/system/constants.php',
            '/system/config/constants.php',
        ];

        if ($this->debug) {
            $contaoFiles[] = '/vendor/contao/contao/manager-bundle/bin/contao-console';
        }

        // Use current folder if it looks like Contao
        foreach ($contaoFiles as $file) {
            if ($this->filesystem->exists($current.$file)) {
                $this->projectDir = $current;
                $this->publicDir = $current;

                return;
            }
        }

        // Throw exception if parent folder looks like Contao but is not writeable
        if (!is_writable(\dirname($current))) {
            $files = [
                \dirname($current).'/vendor/contao/manager-bundle/bin/contao-console',
                \dirname($current).'/system/constants.php',
                \dirname($current).'/system/config/constants.php',
            ];

            if ($this->debug) {
                $files[] = \dirname($current).'/vendor/contao/contao/manager-bundle/bin/contao-console';
            }

            foreach ($files as $file) {
                if ($this->filesystem->exists($file)) {
                    $translator = $this->getTranslator();
                    $problem = (new ApiProblem(
                        $translator->trans('error.writable.root', ['path' => \dirname($current)]),
                        'https://php.net/is_writable',
                    ))->setDetail($translator->trans('error.writable.detail'));

                    throw new ApiProblemException($problem);
                }
            }
        }

        $this->publicDir = $current;
        $this->projectDir = \dirname($current);
    }

    private function ensureDirectoryExists(string $directory): void
    {
        try {
            $this->filesystem->mkdir($directory);
        } catch (IOException $exception) {
            $translator = $this->getTranslator();
            $problem = new ApiProblem($translator->trans('error.writable.directory', ['path' => $exception->getPath()]));
            $problem->setDetail($translator->trans('error.writable.detail'));

            throw new ApiProblemException($problem, $exception);
        }
    }
}
