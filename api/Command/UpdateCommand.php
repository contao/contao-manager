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

use Contao\ManagerApi\System\SelfUpdate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends Command
{
    /**
     * @var SelfUpdate
     */
    private $updater;

    public function __construct(SelfUpdate $selfUpdate)
    {
        parent::__construct();

        $this->updater = $selfUpdate;
    }

    public function isEnabled(): bool
    {
        return '' !== \Phar::running(false) && parent::isEnabled();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
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
        $this
            ->setName('self-update')
            ->setDescription('Updates Contao Manager to the latest version')
        ;
    }

    private function update(OutputInterface $output): int
    {
        $result = $this->updater->update();

        if (false === $result) {
            $output->writeln('<info>Already up-to-date.</info>');
        } else {
            $output->writeln(
                sprintf(
                    'Updated from version %s to version %s.',
                    $this->updater->getOldVersion(),
                    $this->updater->getNewVersion()
                )
            );
        }

        return 0;
    }
}
