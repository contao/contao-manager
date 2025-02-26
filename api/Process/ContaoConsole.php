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
use Composer\Util\Platform;
use Contao\ManagerApi\Exception\ProcessOutputException;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ContaoConsole
{
    private string|null $version = null;

    private array|null $commands = null;

    private array|null $config = null;

    public function __construct(private readonly ConsoleProcessFactory $processFactory)
    {
    }

    /**
     * Gets the Contao version.
     *
     * @throws ProcessOutputException
     */
    public function getVersion(): string
    {
        if (null !== $this->version) {
            return $this->version;
        }

        $this->getCommandList(true);

        // @phpstan-ignore notIdentical.alwaysFalse
        if (null !== $this->version) {
            return $this->version;
        }

        $process = $this->processFactory->createContaoConsoleProcess(['contao:version']);
        $process->run();

        $version = trim($process->getOutput());

        try {
            // Run parser to check whether a valid version was returned
            $parser = new VersionParser();
            $parser->normalize($version);
        } catch (\UnexpectedValueException) {
            throw new ProcessOutputException('Console output is not a valid version string.', $process);
        }

        return $this->version = $version;
    }

    public function getCommandList(bool $throw = false): array
    {
        if (null !== $this->commands) {
            return $this->commands;
        }

        $process = $this->processFactory->createContaoConsoleProcess(['list', '--format=json']);
        $process->run();

        // If the console does not work, we don't have any command support.
        if (!$process->isSuccessful() || !\is_array($data = json_decode(trim($process->getOutput()), true))) {
            if ($throw) {
                throw new ProcessOutputException('Unable to retrieve console commands.', $process);
            }

            return $this->commands = [];
        }

        if (
            'Contao Managed Edition' === ($data['application']['name'] ?? '')
            && isset($data['application']['version'])
        ) {
            try {
                // Run parser to check whether a valid version was returned
                $parser = new VersionParser();
                $parser->normalize($data['application']['version']);

                $this->version = $data['application']['version'];
            } catch (\UnexpectedValueException) {
                // ignore version from command list
            }
        }

        return $this->commands = $this->normalizeCommands($data['commands'] ?? []);
    }

    public function getConfig(): array|null
    {
        if (null !== $this->config) {
            return $this->config;
        }

        $commands = $this->getCommandList();

        if (
            !isset($commands['debug:config']['options'])
            || !\in_array('format', $commands['debug:config']['options'], true)
        ) {
            return $this->config = [];
        }

        $process = $this->processFactory->createContaoConsoleProcess(['debug:config', 'contao', '--format=json', '--resolve-env']);
        $process->run();

        // If the console does not work, we don't have any command support.
        if (!$process->isSuccessful() || !\is_array($data = json_decode(trim($process->getOutput()), true))) {
            return $this->config = [];
        }

        return $this->config = $data['contao'] ?? [];
    }

    public function checkDatabaseMigrations(): array|null
    {
        $commands = $this->getCommandList();

        if (
            !isset($commands['contao:migrate']['options'])
            || !\in_array('format', $commands['contao:migrate']['options'], true)
            || !\in_array('dry-run', $commands['contao:migrate']['options'], true)
        ) {
            return null;
        }

        $arguments = [
            'contao:migrate',
            '--format=ndjson',
            '--dry-run',
            '--no-interaction',
        ];

        if (\in_array('no-backup', $commands['contao:migrate']['options'], true)) {
            $arguments[] = '--no-backup';
        }

        $process = $this->processFactory->createContaoConsoleProcess($arguments);
        $process->run();

        $output = trim($process->getOutput());

        // Process could exit with error but still output JSON
        if (!$process->isSuccessful() && !str_starts_with($output, '{')) {
            return [
                'type' => 'error',
                'total' => 1,
                'message' => $process->getOutput().$process->getErrorOutput(),
                'warnings' => 0,
            ];
        }

        $warnings = 0;

        if ('' !== $output) {
            $lines = explode("\n", $output);

            while ($line = array_shift($lines)) {
                $data = json_decode($line, true);
                $type = $data['type'] ?? null;

                if ('warning' === $type) {
                    ++$warnings;
                    continue;
                }

                if ('error' === $type || 'problem' === $type) {
                    return [
                        'type' => $type,
                        'total' => 1,
                        'message' => $data['message'] ?? '',
                        'warnings' => $warnings,
                    ];
                }

                if ('migration-pending' === $type && !empty($data['names'])) {
                    return [
                        'type' => 'migration',
                        'total' => \count($data['names']),
                        'warnings' => $warnings,
                    ];
                }

                if ('schema-pending' === $type && !empty($data['commands'])) {
                    return [
                        'type' => 'schema',
                        'total' => \count($data['commands']),
                        'warnings' => $warnings,
                    ];
                }
            }
        }

        return [
            'type' => 'empty',
            'total' => 0,
            'warnings' => $warnings,
        ];
    }

    public function getUsers(bool $throw = false): array|null
    {
        $commands = $this->getCommandList();

        if (
            !isset($commands['contao:user:list']['options'])
            || !\in_array('format', $commands['contao:user:list']['options'], true)
            || !\in_array('column', $commands['contao:user:list']['options'], true)
        ) {
            return null;
        }

        $arguments = [
            'contao:user:list',
            '--format=json',
            '--column=username',
            '--column=name',
            '--column=admin',
            '--column=dateAdded',
            '--column=lastLogin',
            '--no-interaction',
        ];

        $process = $this->processFactory->createContaoConsoleProcess($arguments);
        $process->run();

        if (!$process->isSuccessful()) {
            if ($throw) {
                throw new ProcessFailedException($process);
            }

            return null;
        }

        $data = json_decode($process->getOutput(), true);

        if (!\is_array($data)) {
            if ($throw) {
                throw new ProcessOutputException('Unable to list Contao users', $process);
            }

            return null;
        }

        return $data;
    }

    /**
     * @throws \RuntimeException
     * @throws ProcessFailedException
     */
    public function createBackendUser(array $user, string $password, bool $admin = true): void
    {
        $commands = $this->getCommandList();

        if (
            !isset($commands['contao:user:create']['options'])
            || ($admin && !\in_array('admin', $commands['contao:user:create']['options'], true))
            || [] !== array_diff(array_keys($user), $commands['contao:user:create']['options'])
        ) {
            throw new \RuntimeException('Unsupported argument to the contao:user:create command.');
        }

        $arguments = [
            'contao:user:create',
        ];

        foreach ($user as $k => $v) {
            $arguments[] = '--'.$k.'='.$v;
        }

        if ($admin) {
            $arguments[] = '--admin';
        }

        if (Platform::isWindows()) {
            $arguments[] = '--password='.$password;
        }

        $process = $this->processFactory->createContaoConsoleProcess($arguments);

        if (!Platform::isWindows()) {
            $process->setInput($password.PHP_EOL.$password.PHP_EOL); // Password and confirmation
        }

        $process->mustRun();
    }

    private function normalizeCommands(array $commands): array
    {
        $data = [];

        foreach ($commands as $command) {
            $data[$command['name']] = [
                'arguments' => array_keys($command['definition']['arguments'] ?? []),
                'options' => array_keys($command['definition']['options'] ?? []),
            ];
        }

        return $data;
    }
}
