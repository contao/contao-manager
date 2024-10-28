<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Exception;

use Symfony\Component\Process\Process;

class ProcessOutputException extends \RuntimeException
{
    private readonly \Symfony\Component\Process\Process $process;

    public function __construct(string $message, Process $process, \Throwable $previous = null)
    {
        parent::__construct($message, $process->getExitCode(), $previous);

        $this->process = $process;
    }

    /**
     * Gets the process object.
     */
    public function getProcess(): Process
    {
        return $this->process;
    }
}
