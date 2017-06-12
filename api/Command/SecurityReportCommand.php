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
use SensioLabs\Security\Exception\ExceptionInterface;
use SensioLabs\Security\Formatters\SimpleFormatter;
use SensioLabs\Security\SecurityChecker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SecurityReportCommand extends Command
{
    /**
     * @var ApiKernel
     */
    private $kernel;

    /**
     * @var SecurityChecker
     */
    private $checker;

    /**
     * Constructor.
     *
     * @param ApiKernel            $kernel
     * @param SecurityChecker|null $checker
     */
    public function __construct(ApiKernel $kernel, SecurityChecker $checker = null)
    {
        parent::__construct();

        $this->kernel = $kernel;
        $this->checker = $checker ?: new SecurityChecker();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('security-report')
            ->setDescription('Reports security issues in your Contao dependencies')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lockfile = $this->kernel->getContaoDir().'/composer.lock';

        try {
            $vulnerabilities = $this->checker->check($lockfile);
        } catch (ExceptionInterface $e) {
            $output->writeln($this->getHelperSet()->get('formatter')->formatBlock($e->getMessage(), 'error', true));

            return 1;
        }

        $formatter = new SimpleFormatter($this->getHelperSet()->get('formatter'));
        $formatter->displayResults($output, $lockfile, $vulnerabilities);

        if ($this->checker->getLastVulnerabilityCount() > 0) {
            return 1;
        }

        return 0;
    }
}
