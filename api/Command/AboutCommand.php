<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Command;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\System\IpInfo;
use Contao\ManagerApi\System\ServerInfo;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ServiceSubscriberInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class AboutCommand extends Command implements ServiceSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();

        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            ApiKernel::class,
            IpInfo::class,
            ServerInfo::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('about')
            ->setDescription('Displays information about Contao Manager and the current server')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $data = $this->collectData();

        $this->outputTable($input, $output, $data);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param array           $data
     */
    private function outputTable(InputInterface $input, OutputInterface $output, $data)
    {
        $io = new SymfonyStyle($input, $output);

        $osVersion = $data['server']['os_version'] ? ' ('.$data['server']['os_version'].')' : '';
        $country = $data['server']['country'];

        if (class_exists('Locale')) {
            /** @noinspection PhpComposerExtensionStubsInspection */
            $country = \Locale::getDisplayRegion('-'.$country, 'en');
        }

        $rows = [
            ['<info>Contao Manager</info>'],
            new TableSeparator(),
            ['Version', $data['app']['version']],
            ['Environment', $data['app']['env']],
            ['Debug', $data['app']['debug'] ? 'true' : 'false'],
            ['Cache directory', $data['app']['cache_dir']],
            ['Contao directory', $data['app']['contao_dir']],
            ['Data directory', $data['app']['manager_dir']],
            new TableSeparator(),
            ['<info>PHP</info>'],
            new TableSeparator(),
            ['Version', $data['php']['version']],
            ['Architecture', $data['php']['arch'].' bits'],
            ['Server API', $data['php']['sapi']],
            ['Intl locale', $data['php']['locale']],
            ['Timezone', $data['php']['timezone']],
            ['Binary Path', $data['php']['binary'] ?: '-- NOT FOUND --'],
            new TableSeparator(),
            ['<info>Server Infrastructure</info>'],
            new TableSeparator(),
            ['IP', $data['server']['ip']],
            ['Hostname', $data['server']['hostname']],
            ['Network Owner', $data['server']['org']],
            ['Country', $country],
            ['Operating System', $data['server']['os_name'].$osVersion],
        ];

        if (!empty($data['server']['arch'])) {
            $rows[] = ['Architecture', $data['server']['arch']];
        }

        if (!empty($data['provider'])) {
            $rows[] = new TableSeparator();
            $rows[] = ['<info>Server Setup</info>'];
            $rows[] = new TableSeparator();
            $rows[] = ['Name', $data['provider']['name']];
            $rows[] = ['Website', $data['provider']['website']];
        }

        $io->table([], $rows);
    }

    private function collectData()
    {
        $kernel = $this->container->get(ApiKernel::class);
        $ipInfo = $this->container->get(IpInfo::class)->collect();
        $serverInfo = $this->container->get(ServerInfo::class);

        $provider = [];
        $version = $this->getManagerVersion($kernel);

        if (null !== ($configName = $serverInfo->detect())) {
            $provider = $serverInfo->getConfigs()[$configName];
        }

        /** @noinspection PhpComposerExtensionStubsInspection */
        $data = [
            'app' => [
                'version' => $version,
                'env' => $kernel->getEnvironment(),
                'debug' => $kernel->isDebug(),
                'cache_dir' => $kernel->getCacheDir(),
                'log_dir' => $kernel->getLogDir(),
                'contao_dir' => $kernel->getContaoDir(),
                'manager_dir' => $kernel->getManagerDir(),
            ],
            'php' => [
                'version' => PHP_VERSION,
                'version_id' => PHP_VERSION_ID,
                'arch' => PHP_INT_SIZE * 8,
                'sapi' => PHP_SAPI,
                'locale' => class_exists('Locale', false) && \Locale::getDefault() ? \Locale::getDefault() : '',
                'timezone' => date_default_timezone_get(),
                'binary' => $serverInfo->getPhpExecutableFinder()->find(),
            ],
            'server' => array_merge($ipInfo, [
                'os_name' => php_uname('s'),
                'os_version' => php_uname('r'),
                'arch' => php_uname('m'),
            ]),
            'provider' => $provider,
        ];

        if ($data['server']['os_name'] === $data['server']['os_version']) {
            $data['server']['os_version'] = '';
            $data['server']['arch'] = '';
        }

        return $data;
    }

    private function getManagerVersion(ApiKernel $kernel)
    {
        $version = $kernel->getVersion();

        if ($version === ('@'.'package_version'.'@')) {
            $git = new Process('git describe --always');

            try {
                $git->mustRun();
                $version = trim($git->getOutput());
            } catch (ProcessFailedException $e) {
                return 'n/a';
            }
        }

        return $version;
    }
}
