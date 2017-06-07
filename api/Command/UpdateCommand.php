<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Command;

use Contao\ManagerApi\ApiApplication;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method ApiApplication getApplication()
 */
class UpdateCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return \Phar::running(false) !== '' && parent::isEnabled();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $kernel = $this->getApplication()->getKernel();

        if ($kernel->getVersion() === '@'.'package_version'.'@'
            || $kernel->getEnvironment() !== 'prod'
            || $kernel->isDebug()
        ) {
            throw new \RuntimeException('This development build of Contao Manager cannot be automatically updated.');
        }

        return $this->update($output);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('self-update')
            ->setDescription('Updates Contao Manager to the latest version')
        ;
    }

    /**
     * Updates the .phar file.
     *
     * @param OutputInterface $output
     *
     * @return int
     */
    private function update(OutputInterface $output)
    {
        $updater = $this->getContainer()->get('contao_manager.self_update.updater');

        $result = $updater->update();

        if (false === $result) {
            $output->writeln('<info>Already up-to-date.</info>');
        } else {
            $output->writeln(
                sprintf(
                    'Updated from version %s to version %s.',
                    $updater->getOldVersion(),
                    $updater->getNewVersion()
                )
            );
        }

        return 0;
    }
}
