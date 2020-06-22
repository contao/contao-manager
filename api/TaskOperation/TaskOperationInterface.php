<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\TaskOperation;

interface TaskOperationInterface
{
    public function getSummary(): string;

    public function getDetails(): ?string;

    public function getConsole(): ConsoleOutput;

    public function isStarted(): bool;

    public function isRunning(): bool;

    public function isSuccessful(): bool;

    public function hasError(): bool;

    public function run(): void;

    public function abort(): void;

    public function delete(): void;
}
