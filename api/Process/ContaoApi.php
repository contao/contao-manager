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

use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ExceptionInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ContaoApi
{
    /**
     * @var ConsoleProcessFactory
     */
    private $processFactory;

    /**
     * @var Filesystem|null
     */
    private $filesystem;

    /**
     * @var array
     */
    private $apiInfo;

    /**
     * Constructor.
     */
    public function __construct(ConsoleProcessFactory $processFactory, Filesystem $filesystem = null)
    {
        $this->processFactory = $processFactory;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    /**
     * Gets the Contao API version.
     */
    public function getVersion(): int
    {
        return (int) $this->getApiInfo()['version'];
    }

    /**
     * Returns list of available API commands.
     */
    public function getCommands(): array
    {
        return $this->getApiInfo()['commands'];
    }

    /**
     * Returns whether the given API command is available.
     */
    public function hasCommand(string $name): bool
    {
        return \in_array($name, $this->getApiInfo()['commands'], true);
    }

    /**
     * Returns list of available API features.
     */
    public function getFeatures(): array
    {
        return $this->getApiInfo()['features'];
    }

    /**
     * @param string|array $arguments
     *
     * @throws ParsingException
     * @throws ProcessFailedException
     */
    public function runCommand($arguments, bool $parseJson = false): string
    {
        $process = $this->processFactory->createContaoApiProcess((array) $arguments);
        $process->mustRun();

        return $parseJson ? $this->parseJson($process->getOutput()) : $process->getOutput();
    }

    /**
     * Checks whether the Contao API binary exists.
     */
    private function hasBinary(): bool
    {
        return $this->filesystem->exists($this->processFactory->getContaoApiPath());
    }

    /**
     * Returns version, commands and features of the Contao API.
     */
    private function getApiInfo(): array
    {
        if (null !== $this->apiInfo) {
            return $this->apiInfo;
        }

        $default = [
            'version' => 0,
            'commands' => [],
            'features' => [],
        ];

        if (!$this->hasBinary()) {
            return $this->apiInfo = $default;
        }

        try {
            $process = $this->processFactory->createContaoApiProcess(['version']);
            $process->mustRun();
        } catch (ExceptionInterface $exception) {
            return $default;
        }

        $version = trim($process->getOutput());

        if (preg_match('/^\d+$/', $version)) {
            $default['version'] = (int) $version;

            return $this->apiInfo = $default;
        }

        try {
            return $this->apiInfo = $this->parseJson($version);
        } catch (ParsingException $e) {
            $default['error'] = $e->getMessage();

            return $this->apiInfo = $default;
        }
    }

    /**
     * @throws ParsingException
     */
    private function parseJson(string $output)
    {
        $data = json_decode($output, true);

        if (null === $data && JSON_ERROR_NONE !== json_last_error()) {
            $parser = new JsonParser();
            $result = $parser->lint($output);

            if (null !== $result) {
                throw $result;
            }
        }

        return $data;
    }
}
