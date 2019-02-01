<?php

namespace Contao\ManagerApi\Process;

use Symfony\Component\Process\Process;
use Contao\ManagerApi\Process\Forker\ForkerInterface;

class ProcessController extends AbstractProcess
{
    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var ForkerInterface[]
     */
    private $forkers = [];

    /**
     * Constructor.
     *
     * @param array                $config
     * @param string               $workDir
     *
     * @throws \InvalidArgumentException If the working directory does not exist
     */
    public function __construct(array $config, $workDir)
    {
        if (!isset($config['status'])) {
            $config['status'] = Process::STATUS_READY;
        }

        $this->config = $config;

        parent::__construct($this->config['id'], $workDir);
    }

    public function addForker(ForkerInterface $forker)
    {
        $this->forkers[] = $forker;
    }

    /**
     * Gets the task ID.
     *
     * @return string
     */
    public function getId()
    {
        return (string) $this->config['id'];
    }

    /**
     * Stores meta information about the process.
     *
     * @param array $meta
     */
    public function setMeta(array $meta)
    {
        $this->config['meta'] = $meta;
    }

    /**
     * Gets meta information of the process.
     *
     * @return array|null
     */
    public function getMeta()
    {
        return array_key_exists('meta', $this->config) ? $this->config['meta'] : null;
    }

    public function start()
    {
        if ($this->config['status'] === Process::STATUS_STARTED) {
            return;
        }

        $this->saveConfig(true);

        $this->config['status'] = Process::STATUS_STARTED;

        $this->getForker()->run($this->setFile);
    }

    public function getPid()
    {
        $this->updateStatus();

        return $this->config['pid'];
    }

    public function getExitCode()
    {
        $this->updateStatus();

        return isset($this->config['exitcode']) ? (int) $this->config['exitcode'] : null;
    }

    public function getExitCodeText()
    {
        if (null === $exitcode = $this->getExitCode()) {
            return '';
        }

        return isset(Process::$exitCodes[$exitcode]) ? Process::$exitCodes[$exitcode] : 'Unknown error';
    }

    public function isSuccessful()
    {
        return 0 === $this->getExitCode();
    }

    public function hasBeenSignaled()
    {
        return isset($this->config['signaled']) ? (bool) $this->config['signaled'] : false;
    }

    public function getTermSignal()
    {
        return isset($this->config['termsig']) ? (int) $this->config['termsig'] : null;
    }

    public function hasBeenStopped()
    {
        return isset($this->config['stopped']) ? (bool) $this->config['stopped'] : false;
    }

    public function getStopSignal()
    {
        return isset($this->config['stopsig']) ? (int) $this->config['stopsig'] : null;
    }

    public function isRunning()
    {
        return Process::STATUS_STARTED === $this->getStatus();
    }

    public function isStarted()
    {
        return Process::STATUS_READY !== $this->getStatus();
    }

    public function isTerminated()
    {
        return Process::STATUS_TERMINATED === $this->getStatus();
    }

    public function isTimedOut()
    {
        return Process::STATUS_TERMINATED === $this->getStatus() && $this->config['timedout'] > 0;
    }

    public function getStatus()
    {
        $this->updateStatus();

        return $this->config['status'];
    }

    public function stop()
    {
        $this->config['stop'] = true;

        $this->saveConfig();
    }

    public function delete()
    {
        if ($this->isRunning()) {
            throw new \LogicException('Cannot delete a running process.');
        }

        $this->close();
    }

    public function getCommandLine()
    {
        return $this->config['commandline'];
    }

    public function setCommandLine($commandline)
    {
        $this->config['commandline'] = $commandline;

        $this->saveConfig();
    }

    public function setWorkingDirectory($cwd)
    {
        $this->config['cwd'] = $cwd;

        $this->saveConfig();
    }

    public function getOutput()
    {
        if (!is_file($this->outputFile)) {
            return '';
        }

        return file_get_contents($this->outputFile);
    }

    public function getErrorOutput()
    {
        if (!is_file($this->errorOutputFile)) {
            return '';
        }

        return file_get_contents($this->errorOutputFile);
    }

    public function setTimeout($timeout)
    {
        $this->config['timeout'] = $timeout;

        $this->saveConfig();
    }

    public function setIdleTimeout($timeout)
    {
        $this->config['idleTimeout'] = $timeout;

        $this->saveConfig();
    }

    private function getForker()
    {
        foreach ($this->forkers as $forker) {
            if ($forker->isSupported()) {
                return $forker;
            }
        }

        throw new \RuntimeException('No forker found for your current platform.');
    }

    private function saveConfig($always = false)
    {
        if ($always || Process::STATUS_STARTED === $this->config['status']) {
            static::writeConfig($this->setFile, $this->config);
        }
    }

    private function updateStatus()
    {
        if (Process::STATUS_STARTED !== $this->config['status']) {
            return;
        }

        if (is_file($this->getFile)) {
            $this->config = array_merge($this->config, static::readConfig($this->getFile));
        }
    }

    private function close()
    {
        @unlink($this->setFile);
        @unlink($this->getFile);
        @unlink($this->inputFile);
        @unlink($this->outputFile);
        @unlink($this->errorOutputFile);
    }

    public static function create($workDir, $commandline, $cwd = null, $id = null)
    {
        return new static(
            [
                'id' => $id ?: md5(uniqid('', true)),
                'commandline' => $commandline,
                'cwd' => $cwd ?: getcwd(),
            ],
            $workDir
        );
    }

    public static function restore($workDir, $id)
    {
        $config = static::readConfig($workDir.'/'.$id.'.set.json');

        if (is_file($getFile = $workDir.'/'.$id.'.get.json')) {
            $config = array_merge($config, static::readConfig($getFile));
        }

        return new static($config, $workDir);
    }
}
