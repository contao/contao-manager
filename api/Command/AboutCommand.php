<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Command;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\System\ServerInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class AboutCommand extends Command
{
    public function __construct(private readonly ApiKernel $kernel, private readonly ServerInfo $serverInfo)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('about')
            ->setDescription('Displays information about Contao Manager and the current server')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $data = $this->collectData();

        $this->outputTable($input, $output, $data);

        return Command::SUCCESS;
    }

    private function outputTable(InputInterface $input, OutputInterface $output, array $data): void
    {
        $io = new SymfonyStyle($input, $output);

        $osVersion = $data['server']['os_version'] ? ' ('.$data['server']['os_version'].')' : '';

        $rows = [
            ['<info>Contao Manager</info>'],
            new TableSeparator(),
            ['Version', $data['app']['version']],
            ['Environment', $data['app']['env']],
            ['Debug', $data['app']['debug'] ? 'true' : 'false'],
            ['Cache directory', $data['app']['cache_dir']],
            ['Contao directory', $data['app']['project_dir']],
            ['Data directory', $data['app']['config_dir']],
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
            ['<info>Server</info>'],
            new TableSeparator(),
            ['Operating System', $data['server']['os_name'].$osVersion],
        ];

        if (!empty($data['server']['arch'])) {
            $rows[] = ['Architecture', $data['server']['arch']];
        }

        $io->table([], $rows);
    }

    private function collectData(): array
    {
        $version = $this->getManagerVersion($this->kernel);

        $data = [
            'app' => [
                'version' => $version,
                'env' => $this->kernel->getEnvironment(),
                'debug' => $this->kernel->isDebug(),
                'cache_dir' => $this->kernel->getCacheDir(),
                'log_dir' => $this->kernel->getLogDir(),
                'project_dir' => $this->kernel->getProjectDir(),
                'config_dir' => $this->kernel->getConfigDir(),
            ],
            'php' => [
                'version' => PHP_VERSION,
                'version_id' => \PHP_VERSION_ID,
                'arch' => PHP_INT_SIZE * 8,
                'sapi' => \PHP_SAPI,
                'locale' => class_exists('Locale', false) && \Locale::getDefault() ? \Locale::getDefault() : '',
                'timezone' => date_default_timezone_get(),
                'binary' => $this->serverInfo->getPhpExecutable(),
            ],
            'server' => [
                'os_name' => php_uname('s'),
                'os_version' => php_uname('r'),
                'arch' => php_uname('m'),
            ],
        ];

        if ($data['server']['os_name'] === $data['server']['os_version']) {
            $data['server']['os_version'] = '';
            $data['server']['arch'] = '';
        }

        return $data;
    }

    private function getManagerVersion(ApiKernel $kernel): string
    {
        $version = $kernel->getVersion();

        if ($version === '@manager_version'.'@') {
            $git = new Process(['git', 'describe', '--tags', '--always']);

            try {
                $git->mustRun();
                $version = trim($git->getOutput());
            } catch (ProcessFailedException) {
                return 'n/a';
            }
        }

        return $version;
    }
}
