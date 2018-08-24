<?php

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
use Symfony\Component\Process\Exception\ProcessFailedException;

class ContaoApi
{
    /**
     * @var ConsoleProcessFactory
     */
    private $processFactory;

    /**
     * @var null|Filesystem
     */
    private $filesystem;

    /**
     * @var array
     */
    private $apiInfo;

    /**
     * Constructor.
     *
     * @param ConsoleProcessFactory $processFactory
     * @param Filesystem|null       $filesystem
     */
    public function __construct(ConsoleProcessFactory $processFactory, Filesystem $filesystem = null)
    {
        $this->processFactory = $processFactory;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    /**
     * Gets the Contao API version.
     *
     * @return int
     */
    public function getVersion()
    {
        return $this->getApiInfo()['version'];
    }

    /**
     * Returns list of available API commands.
     *
     * @return array
     */
    public function getCommands()
    {
        return $this->getApiInfo()['commands'];
    }

    /**
     * Returns whether the given API command is available.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasCommand($name)
    {
        return in_array($name, $this->getApiInfo()['commands'], true);
    }

    /**
     * Returns list of available API features.
     *
     * @return array
     */
    public function getFeatures()
    {
        return $this->getApiInfo()['features'];
    }

    /**
     * @param string|array $arguments
     * @param bool         $parseJson
     *
     * @throws ParsingException
     * @throws ProcessFailedException
     *
     * @return string
     */
    public function runCommand($arguments, $parseJson = false)
    {
        $process = $this->processFactory->createContaoApiProcess((array) $arguments);
        $process->mustRun();

        return $parseJson ? $this->parseJson($process->getOutput()) : $process->getOutput();
    }

    /**
     * Checks whether the Contao API binary exists.
     *
     * @return bool
     */
    private function hasBinary()
    {
        return $this->filesystem->exists($this->processFactory->getContaoApiPath());
    }

    /**
     * Returns version, commands and features of the Contao API.
     *
     * @return array
     */
    private function getApiInfo()
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

        $process = $this->processFactory->createContaoApiProcess(['version']);
        $process->mustRun();

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
     * @param string $output
     *
     * @throws ParsingException
     *
     * @return mixed
     */
    private function parseJson($output)
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
