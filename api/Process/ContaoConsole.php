<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Process;

use Composer\Semver\VersionParser;
use Contao\ManagerApi\Exception\ProcessOutputException;

class ContaoConsole
{
    /**
     * @var ConsoleProcessFactory
     */
    private $processFactory;

    /**
     * Constructor.
     */
    public function __construct(ConsoleProcessFactory $processFactory)
    {
        $this->processFactory = $processFactory;
    }

    /**
     * Gets the Contao version.
     *
     * @throws ProcessOutputException
     */
    public function getVersion(): string
    {
        $process = $this->processFactory->createContaoConsoleProcess(['contao:version']);
        $process->run();

        $version = '';
        $lines = preg_split('/\r\n|\r|\n/', $process->getOutput());

        while ($line = array_shift($lines)) {
            if (0 === strpos($line, 'PHP Warning:')
                || 0 === strpos($line, 'Warning:')
                || 0 === strpos($line, 'Failed loading ')
            ) {
                continue;
            }

            $version = trim($line."\n".implode("\n", $lines));
            break;
        }

        try {
            // Run parser to check whether a valid version was returned
            $parser = new VersionParser();
            $parser->normalize($version);
        } catch (\UnexpectedValueException $e) {
            throw new ProcessOutputException('Console output is not a valid version string.', $process);
        }

        return $version;
    }
}
