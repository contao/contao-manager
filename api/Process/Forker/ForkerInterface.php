<?php

namespace Contao\ManagerApi\Process\Forker;

interface ForkerInterface
{
    /**
     * Sets the executable to use for the background process.
     *
     * @param string $executable
     */
    public function setExecutable($executable);

    /**
     * Gets the executable to use for the background process.
     *
     * @return string
     */
    public function getExecutable();

    /**
     * Sets the timeout in milliseconds to wait after starting a process.
     *
     * @param int $timeout
     *
     * @return $this
     */
    public function setTimeout($timeout);

    /**
     * Gets the timeout in milliseconds to wait after starting a process.
     *
     * @return int
     */
    public function getTimeout();

    /**
     * Executes a command.
     *
     * @param string $configFile
     */
    public function run($configFile);

    /**
     * Returns whether this forker is supported on the current platform.
     *
     * @return bool
     */
    public function isSupported();
}
