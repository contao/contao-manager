<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\Exception;

use Symfony\Component\Process\Process;

class ProcessOutputException extends \RuntimeException
{
    /**
     * @var Process
     */
    private $process;

    /**
     * Constructor.
     *
     * @param string          $message
     * @param Process         $process
     * @param \Exception|null $previous
     */
    public function __construct($message, Process $process, \Exception $previous = null)
    {
        parent::__construct($message, $process->getExitCode(), $previous);

        $this->process = $process;
    }

    /**
     * Gets the process object.
     *
     * @return Process
     */
    public function getProcess()
    {
        return $this->process;
    }
}
