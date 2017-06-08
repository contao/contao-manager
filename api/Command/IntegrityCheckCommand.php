<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Command;

use Contao\ManagerApi\IntegrityCheck\IntegrityCheckInterface;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IntegrityCheckCommand extends Command
{
    /**
     * @var IntegrityCheckInterface[]
     */
    private $checks = [];

    /**
     * Adds an integrity check.
     *
     * @param IntegrityCheckInterface $check
     */
    public function addIntegrityCheck(IntegrityCheckInterface $check)
    {
        $this->checks[] = $check;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('integrity-check')
            ->setDescription('Performs integrity check for the Contao Manager')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Use "text", "json" or "xml" to output the check results accordingly.', 'text')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->checks as $check) {
            if (($problem = $check->run()) instanceof ApiProblem) {
                $this->reportProblem($input, $output, $problem);

                return 1;
            }
        }

        $output->writeln('<info>All checks successful.</info>');

        return 0;
    }

    private function reportProblem(InputInterface $input, OutputInterface $output, ApiProblem $problem)
    {
        $format = $input->getOption('format');

        switch ($format) {
            case 'text':
                $output->writeln($problem->getTitle());

                if ($detail = $problem->getDetail()) {
                    $output->writeln('');
                    $output->writeln($detail);
                }
                break;

            case 'json':
                $output->write($problem->asJson(true));
                break;

            case 'xml':
                $output->write($problem->asXml(true));
                break;

            default:
                throw new \InvalidArgumentException(sprintf('Unknown output format "%s"', $format));
        }
    }
}
