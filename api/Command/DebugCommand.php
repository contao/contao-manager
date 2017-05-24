<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DebugCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('contao-manager:debug')
            ->setDescription('Returns the current PHP version.')
            ->addOption('property', null, InputOption::VALUE_REQUIRED)
            ->addOption('json', null, InputOption::VALUE_NONE)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $props = [
            'hostname' => php_uname('n'),
            'os_name' => php_uname('s'),
            'os_version' => php_uname('r'),
            'php_version' => PHP_VERSION,
            'php_version_id' => PHP_VERSION_ID,
            'php_sapi' => PHP_SAPI,
        ];

        if ($one = $input->getOption('property')) {
            $this->output($input, $output, $props[$one]);

            return;
        }

        $this->output($input, $output, $props);
    }

    private function output(InputInterface $input, OutputInterface $output, $value)
    {
        if ($input->getOption('json')) {
            $output->writeln(json_encode($value, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        } elseif (is_array($value)) {
            foreach ($value as $k => $v) {
                $output->writeln(sprintf('%s: %s', $k, (string) $v));
            }
        } else {
            $output->writeln($value);
        }
    }
}
