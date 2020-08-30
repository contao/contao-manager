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

use Contao\ManagerApi\Exception\InvalidJsonException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractProcess
{
    protected $setFile;
    protected $getFile;
    protected $inputFile;
    protected $outputFile;
    protected $errorOutputFile;

    /**
     * @throws \InvalidArgumentException If the working directory does not exist
     */
    public function __construct(string $id, string $workDir)
    {
        $workDir = realpath(rtrim($workDir, '/'));

        if (false === $workDir) {
            throw new \InvalidArgumentException(sprintf('Working directory "%s" does not exist.', $workDir));
        }

        $this->setFile = $workDir.'/'.$id.'.set.json';
        $this->getFile = $workDir.'/'.$id.'.get.json';
        $this->inputFile = $workDir.'/'.$id.'.in.log';
        $this->outputFile = $workDir.'/'.$id.'.out.log';
        $this->errorOutputFile = $workDir.'/'.$id.'.err.log';
    }

    /**
     * @throws InvalidJsonException
     */
    protected static function readConfig(string $filename): array
    {
        // Make sure new process files are found (see https://github.com/contao/contao-manager/issues/438)
        clearstatcache();

        if (!is_readable($filename)) {
            throw new \InvalidArgumentException(sprintf('Config file "%s" does not exist or is not readable.', $filename));
        }

        $content = file_get_contents($filename);
        $config = json_decode($content, true);

        if (!\is_array($config)) {
            throw new InvalidJsonException($filename, $content);
        }

        return $config;
    }

    /**
     * @throws \RuntimeException
     */
    protected static function writeConfig(string $filename, array $config): void
    {
        try {
            (new Filesystem())->dumpFile($filename, json_encode($config));
        } catch (IOException $e) {
            throw new \RuntimeException(sprintf('Unable to write config file to %s', $filename));
        }
    }
}
