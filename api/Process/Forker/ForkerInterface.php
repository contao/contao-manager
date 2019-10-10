<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Process\Forker;

use Psr\Log\LoggerInterface;

interface ForkerInterface
{
    public function __construct(array $arguments, array $env = null, LoggerInterface $logger = null);

    /**
     * Sets the command to use for the background process.
     */
    public function setCommand(array $command): ForkerInterface;

    /**
     * Gets the command to use for the background process.
     */
    public function getCommand(): array;

    /**
     * Sets the timeout in milliseconds to wait after starting a process.
     */
    public function setTimeout(int $timeout): ForkerInterface;

    /**
     * Gets the timeout in milliseconds to wait after starting a process.
     */
    public function getTimeout(): int;

    /**
     * Executes a command.
     */
    public function run(string $configFile): void;

    /**
     * Returns whether this forker is supported on the current platform.
     *
     * @return bool
     */
    public function isSupported(): bool;
}
