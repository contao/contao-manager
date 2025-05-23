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
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'integrity-check', description: 'Performs integrity check for the Contao Manager')]
class IntegrityCheckCommand extends Command
{
    public function __construct(private readonly IntegrityCheckFactory $integrity)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
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
            throw new \InvalidArgumentException(\sprintf('Unknown output format "%s"', $format));
        }

        if (null !== $problem) {
            $output->writeln('Running PHP '.PHP_VERSION);
            $output->writeln($problem->getTitle());

            if ('' !== ($detail = $problem->getDetail())) {
                $output->writeln('');
                $output->writeln($detail);
            }

            return Command::FAILURE;
        }

        $output->writeln('<info>Running PHP '.PHP_VERSION.', all checks successful.</info>');

        return Command::SUCCESS;
    }

    private function writeJson(OutputInterface $output, ApiProblem|null $problem = null): int
    {
        $output->write(
            json_encode(
                [
                    'version' => PHP_VERSION,
                    'version_id' => \PHP_VERSION_ID,
                    'problem' => $problem?->asArray(),
                ],
                JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT,
            ),
        );

        return null === $problem ? Command::SUCCESS : Command::FAILURE;
    }
}
