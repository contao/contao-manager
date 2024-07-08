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

use Contao\ManagerApi\IntegrityCheck\IntegrityCheckFactory;
use Crell\ApiProblem\ApiProblem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IntegrityCheckCommand extends Command
{
    /**
     * @var IntegrityCheckFactory
     */
    private $integrity;

    public function __construct(IntegrityCheckFactory $integrity)
    {
        parent::__construct();

        $this->integrity = $integrity;
    }

    protected function configure(): void
    {
        $this
            ->setName('integrity-check')
            ->setDescription('Performs integrity check for the Contao Manager')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Use "text" or "json" to output the check results accordingly.', 'text')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $problem = $this->integrity->runCliCheck();
        $format = $input->getOption('format');

        if ('json' === $format) {
            return $this->writeJson($output, $problem);
        }

        if ('text' !== $format) {
            throw new \InvalidArgumentException(sprintf('Unknown output format "%s"', $format));
        }

        if ($problem) {
            $output->writeln('Running PHP '.PHP_VERSION);
            $output->writeln($problem->getTitle());

            if ($detail = $problem->getDetail()) {
                $output->writeln('');
                $output->writeln($detail);
            }

            return Command::FAILURE;
        }

        $output->writeln('<info>Running PHP '.PHP_VERSION.', all checks successful.</info>');

        return Command::SUCCESS;
    }

    private function writeJson(OutputInterface $output, ApiProblem $problem = null): int
    {
        $output->write(
            json_encode(
                [
                    'version' => PHP_VERSION,
                    'version_id' => \PHP_VERSION_ID,
                    'problem' => $problem ? $problem->asArray() : null,
                ],
                JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
            )
        );

        return $problem ? Command::FAILURE : Command::SUCCESS;
    }
}
