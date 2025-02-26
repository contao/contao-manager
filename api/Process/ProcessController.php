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

use Contao\ManagerApi\Process\Forker\ForkerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class ProcessController extends AbstractProcess
{
    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var array<ForkerInterface>
     */
    private array $forkers = [];

    /**
     * @throws \InvalidArgumentException If the working directory does not exist
     */
    public function __construct(array $config, string $workDir)
    {
        if (!isset($config['status'])) {
            $config['status'] = Process::STATUS_READY;
        }

        $this->config = $config;

        parent::__construct($this->config['id'], $workDir);
    }

    public function addForker(ForkerInterface $forker): void
    {
        $this->forkers[] = $forker;
    }

    /**
     * Gets the task ID.
     */
    public function getId(): string
    {
        return (string) $this->config['id'];
    }

    /**
     * Stores meta information about the process.
     */
    public function setMeta(array $meta): void
    {
        $this->config['meta'] = $meta;

        $this->saveConfig(true);
    }

    /**
     * Gets meta information of the process.
     */
    public function getMeta(): array|null
    {
        return $this->config['meta'] ?? null;
    }

    public function start(): void
    {
        if (Process::STATUS_STARTED === $this->config['status']) {
            return;
        }

        $forker = $this->getForker();
        $this->saveConfig(true);

        $this->config['status'] = Process::STATUS_STARTED;

        $forker->run($this->setFile);
    }

    public function getPid(): int|null
    {
        $this->updateStatus();

        return isset($this->config['pid']) ? (int) $this->config['pid'] : null;
    }

    public function getExitCode(): int|null
    {
        $this->updateStatus();

        return isset($this->config['exitcode']) ? (int) $this->config['exitcode'] : null;
    }

    public function getExitCodeText(): string
    {
        if (null === $exitcode = $this->getExitCode()) {
            return '';
        }

        return Process::$exitCodes[$exitcode] ?? 'Unknown error';
    }

    public function isSuccessful(): bool
    {
        return 0 === $this->getExitCode();
    }

    public function hasBeenSignaled(): bool
    {
        return isset($this->config['signaled']) && $this->config['signaled'];
    }

    public function getTermSignal(): int|null
    {
        return isset($this->config['termsig']) ? (int) $this->config['termsig'] : null;
    }

    public function hasBeenStopped(): bool
    {
        return isset($this->config['stopped']) && $this->config['stopped'];
    }

    public function getStopSignal(): int|null
    {
        return isset($this->config['stopsig']) ? (int) $this->config['stopsig'] : null;
    }

    public function isRunning(): bool
    {
        return Process::STATUS_STARTED === $this->getStatus();
    }

    public function isStarted(): bool
    {
        return Process::STATUS_READY !== $this->getStatus();
    }

    public function isTerminated(): bool
    {
        return Process::STATUS_TERMINATED === $this->getStatus();
    }

    public function isTimedOut(): bool
    {
        return Process::STATUS_TERMINATED === $this->getStatus() && $this->config['timedout'] > 0;
    }

    public function getStatus(): string
    {
        $this->updateStatus();

        return $this->config['status'];
    }

    public function stop(): void
    {
        $this->config['stop'] = true;

        $this->saveConfig();
    }

    public function delete(): void
    {
        if ($this->isRunning()) {
            throw new \LogicException('Cannot delete a running process.');
        }

        $this->close();
    }

    public function getCommandLine(): string
    {
        if (!\is_array($this->config['commandline'])) {
            return (string) $this->config['commandline'];
        }

        return implode(' ', $this->config['commandline']);
    }

    public function setCommandLine(array $commandline): void
    {
        $this->config['commandline'] = $commandline;

        $this->saveConfig();
    }

    public function setWorkingDirectory(string $cwd): void
    {
        $this->config['cwd'] = $cwd;

        $this->saveConfig();
    }

    public function getOutput(): string
    {
        if (!is_file($this->outputFile)) {
            return '';
        }

        return file_get_contents($this->outputFile);
    }

    public function setTimeout(int $timeout): void
    {
        $this->config['timeout'] = $timeout;

        $this->saveConfig();
    }

    public function setIdleTimeout(int $timeout): void
    {
        $this->config['idleTimeout'] = $timeout;

        $this->saveConfig();
    }

    public function getForker(): ForkerInterface
    {
        $class = $this->config['forker'] ?? null;

        foreach ($this->forkers as $forker) {
            if (null === $class ? $forker->isSupported() : $forker instanceof $class) {
                $this->config['forker'] = $forker::class;

                return $forker;
            }
        }

        throw new \RuntimeException('No forker found for your current platform.');
    }

    public static function create(string $workDir, array $commandline, string|null $cwd = null, string|null $id = null): self
    {
        return new self(
            [
                'id' => $id ?: md5(uniqid('', true)),
                'commandline' => $commandline,
                'cwd' => $cwd ?: getcwd(),
            ],
            $workDir,
        );
    }

    public static function restore(string $workDir, string $id): self
    {
        $config = static::readConfig($workDir.'/'.$id.'.set.json');

        if (is_file($getFile = $workDir.'/'.$id.'.get.json')) {
            $config = array_merge($config, static::readConfig($getFile));
        }

        return new self($config, $workDir);
    }

    private function saveConfig(bool $always = false): void
    {
        if ($always || Process::STATUS_STARTED === $this->config['status']) {
            static::writeConfig($this->setFile, $this->config);
        }
    }

    private function updateStatus(): void
    {
        if (Process::STATUS_STARTED !== $this->config['status']) {
            return;
        }

        if (is_file($this->getFile)) {
            $this->config = array_merge($this->config, static::readConfig($this->getFile));
        }
    }

    private function close(): void
    {
        $fs = new Filesystem();

        $fs->remove($this->setFile);
        $fs->remove($this->getFile);
        $fs->remove($this->inputFile);
        $fs->remove($this->outputFile);
    }
}
