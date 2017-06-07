<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Command;

use Contao\ManagerApi\ApiKernel;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AboutCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('about')
            ->setDescription('Displays information about Contao Manager and the current server')
            ->addOption('json', null, InputOption::VALUE_NONE)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ApiKernel $kernel */
        $kernel = $this->getContainer()->get('kernel');
        $data = $kernel->getServerInfo();

        if ($input->getOption('json')) {
            $output->writeln(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            return;
        }

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
            ['Intl locale', $data['php']['locale']],
            ['Timezone', $data['php']['timezone']],
            new TableSeparator(),
            ['<info>Server</info>'],
            new TableSeparator(),
            ['Hostname', $data['server']['hostname']],
            ['Operating System', $data['server']['os_name'].' ('.$data['server']['os_version'].')'],
            ['Architecture', $data['server']['arch']],
        ];

        if (!empty($data['provider'])) {
            $rows[] = new TableSeparator();
            $rows[] = ['<info>Hosting Provider</info>'];
            $rows[] = new TableSeparator();
            $rows[] = ['Name', $data['provider']['name']];
            $rows[] = ['Website', $data['provider']['website']];
        }

        $io->table([], $rows);
    }
}
