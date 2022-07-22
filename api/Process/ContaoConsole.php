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
use Symfony\Component\Process\Exception\ProcessFailedException;

class ContaoConsole
{
    /**
     * @var ConsoleProcessFactory
     */
    private $processFactory;

    /**
     * @var string|null
     */
    private $version;

    /**
     * @var array|null
     */
    private $commands;

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
        if (null !== $this->version) {
            return $this->version;
        }

        $this->getCommandList(true);

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
        } catch (\UnexpectedValueException $e) {
            throw new ProcessOutputException('Console output is not a valid version string.', $process);
        }

        return $this->version = $version;
    }

    public function getCommandList(bool $mustRun = false): array
    {
        if (null !== $this->commands) {
            return $this->commands;
        }

        $process = $this->processFactory->createContaoConsoleProcess(['list', '--format=json']);
        $mustRun ? $process->mustRun() : $process->run();

        $data = json_decode(trim($process->getOutput()), true);

        // If the console does not work, we don't have any command support.
        if (!\is_array($data)) {
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
            } catch (\UnexpectedValueException $e) {
                // ignore version from command list
            }
        }

        return $this->commands = $this->normalizeCommands($data['commands'] ?? []);
    }

    public function checkDatabaseMigrations(): ?array
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

        if (!$process->isSuccessful()) {
            return [
                'type' => 'error',
                'message' => $process->getOutput().$process->getErrorOutput(),
            ];
        }

        $output = $process->getOutput();

        if (!empty($output)) {
            $lines = explode("\n", $output);

            while ($line = array_shift($lines)) {
                $data = json_decode($line, true);

                if ('error' === ($data['type'] ?? null)) {
                    return [
                        'type' => 'error',
                        'message' => $data['message'] ?? '',
                    ];
                }

                if ('migration-pending' === ($data['type'] ?? '') && !empty($data['names'])) {
                    return [
                        'type' => 'migration',
                        'total' => \count($data['names']),
                    ];
                }

                if ('schema-pending' === ($data['type'] ?? '') && !empty($data['commands'])) {
                    return [
                        'type' => 'schema',
                        'total' => \count($data['commands']),
                    ];
                }
            }
        }

        return [
            'type' => 'empty',
        ];
    }

    public function getUsers(): ?array
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
            return null;
        }

        $data = json_decode($process->getOutput(), true);

        return \is_array($data) ? $data : null;
    }

    /**
     * @throws ProcessFailedException
     */
    public function createAdminUser(array $user): void
    {
        $commands = $this->getCommandList();

        if (
            !isset($commands['contao:user:create']['options'])
            || !\in_array('username', $commands['contao:user:create']['options'], true)
            || !\in_array('name', $commands['contao:user:create']['options'], true)
            || !\in_array('email', $commands['contao:user:create']['options'], true)
            || !\in_array('password', $commands['contao:user:create']['options'], true)
            || !\in_array('admin', $commands['contao:user:create']['options'], true)
        ) {
            return;
        }

        $arguments = [
            'contao:user:create',
            '--admin',
            '--username='.$user['username'],
            '--name='.$user['name'],
            '--email='.$user['email'],
            '--password='.$user['password'],
            '--no-interaction',
        ];

        $process = $this->processFactory->createContaoConsoleProcess($arguments);
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
