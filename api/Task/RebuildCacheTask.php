<?php

namespace Contao\ManagerApi\Task;

use Contao\ManagerApi\ApiKernel;
use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\Process\ConsoleProcessFactory;
use Symfony\Component\Filesystem\Filesystem;

class RebuildCacheTask extends AbstractTask
{
    /**
     * @var ApiKernel
     */
    private $kernel;

    /**
     * @var ConsoleProcessFactory
     */
    private $processFactory;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var Filesystem
     */
    private $filesystem;


    /**
     * Constructor.
     *
     * @param ApiKernel             $kernel
     * @param ConsoleProcessFactory $processFactory
     * @param Translator            $translator
     * @param Filesystem            $filesystem
     */
    public function __construct(
        ApiKernel $kernel,
        ConsoleProcessFactory $processFactory,
        Translator $translator,
        Filesystem $filesystem
    ) {
        $this->kernel = $kernel;
        $this->processFactory = $processFactory;
        $this->translator = $translator;
        $this->filesystem = $filesystem;
    }

    public function update(TaskConfig $config)
    {
        $status = new TaskStatus($this->translator->trans('task.rebuild_cache.title'));

        $pClear = $this->getProcess('cache-clear', ['cache:clear', '--no-warmup']);
        $pWarmup = $this->getProcess('cache-warmup', ['cache:warmup']);

        if (!$config->getStatus()) {
            $environment = $config->getOption('environment', 'prod');
            $cacheDir = $this->kernel->getContaoDir() . '/var/cache/' . $environment;
            $this->filesystem->remove($cacheDir);

            $status->setSummary('Deleting cache directory …');
            $status->setDetail($cacheDir);
            $status->setProgress(20);

            $config->setStatus('running');

        } elseif ('stopping' === $config->getStatus()) {

            if (!$pClear->isRunning() && !$pWarmup->isRunning()) {
                $status->setStatus(TaskStatus::STATUS_STOPPED);

                return $status;
            }

            $status->setSummary('Stopping processes …');

            $pClear->stop();
            $pWarmup->stop();

            return $status;

        } elseif (!$pClear->isTerminated()) {
            $status->setSummary('Clearing application cache …');
            $status->setDetail($pClear->getCommandLine());
            $status->setConsole($pClear->getOutput());
            $status->setProgress(40);

            if (!$pClear->isStarted()) {
                $pClear->start();
                $status->setProgress(20);
            }

        } elseif ($pClear->isSuccessful() && !$pWarmup->isTerminated()) {

            $status->setSummary('Warming application cache …');
            $status->setDetail($pWarmup->getCommandLine());
            $status->setConsole($pClear->getOutput() . $pWarmup->getOutput());
            $status->setProgress(80);

            if (!$pWarmup->isStarted()) {
                $pWarmup->start();
                $status->setProgress(60);
            }

        } elseif (!$pClear->isSuccessful()) {

            $status->setSummary('Failed to clear application cache');
            $status->setDetail($pClear->getCommandLine());
            $status->setConsole($pClear->getOutput());
            $status->setStatus(TaskStatus::STATUS_ERROR);

        } elseif (!$pWarmup->isSuccessful()) {

            $status->setSummary('Failed to warm application cache');
            $status->setDetail($pWarmup->getCommandLine());
            $status->setConsole($pClear->getOutput() . $pClear->getOutput());
            $status->setStatus(TaskStatus::STATUS_ERROR);

        } else {
            $status->setSummary('Cache cleared successfully');
            $status->setConsole($pClear->getOutput() . $pWarmup->getOutput());
            $status->setProgress(100);
            $status->setStatus(TaskStatus::STATUS_COMPLETE);
        }

        return $status;
    }

    public function stop(TaskConfig $config)
    {
        $config->setStatus('stopping');

        return $this->update($config);
    }

    public function delete(TaskConfig $config)
    {
        $status = $this->stop($config);

        if (!$status->isActive()) {
            $this->getProcess('cache-clear', [])->delete();
            $this->getProcess('cache-warmup', [])->delete();
        }

        return $status;
    }

    /**
     * @param string $id
     * @param array  $arguments
     *
     * @return \Terminal42\BackgroundProcess\ProcessController
     */
    private function getProcess($id, array $arguments)
    {
        try {
            return $this->processFactory->restoreBackgroundProcess($id);
        } catch (\Exception $e) {
            return $this->processFactory->createContaoConsoleBackgroundProcess($arguments, $id);
        }
    }
}
