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
use Contao\ManagerApi\System\SelfUpdate;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'self-update', description: 'Updates Contao Manager to the latest version')]
class UpdateCommand extends Command
{
    public function __construct(private readonly SelfUpdate $updater)
    {
        parent::__construct();
    }

    public function isEnabled(): bool
    {
        return ApiKernel::isPhar() && parent::isEnabled();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->updater->supportsUpdate()) {
            throw new \RuntimeException('Your server does not meet the requirements of the next Contao Manager version.');
        }

        if (!$this->updater->canUpdate()) {
            throw new \RuntimeException('This build of Contao Manager cannot be automatically updated.');
        }

        return $this->update($output);
    }

    protected function configure(): void
    {
    }

    private function update(OutputInterface $output): int
    {
        $result = $this->updater->update();

        if (false === $result) {
            $output->writeln('<info>Already up-to-date.</info>');
        } else {
            $output->writeln(
                \sprintf(
                    'Updated from version %s to version %s.',
                    $this->updater->getOldVersion(),
                    $this->updater->getNewVersion(),
                ),
            );
        }

        return Command::SUCCESS;
    }
}
