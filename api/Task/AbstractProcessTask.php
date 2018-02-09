<?php

namespace Contao\ManagerApi\Task;

use Contao\ManagerApi\I18n\Translator;
use Symfony\Component\Process\Process;
use Terminal42\BackgroundProcess\ProcessController;

abstract class AbstractProcessTask extends AbstractTask
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * Constructor.
     *
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param TaskConfig $config
     *
     * @return TaskStatus
     */
    public function update(TaskConfig $config)
    {
        $status = $this->getInitialStatus($config);

        $process = $this->getProcess($config);

        if (!$status->getDetail()) {
            $status->setDetail($process->getCommandLine());
        }

        $status->setConsole($process->getOutput().$process->getErrorOutput());

        if ('stopping' === $config->getStatus()) {

            if (!$process->isRunning()) {
                $status->setStatus(TaskStatus::STATUS_STOPPED);

                return $status;
            }

            $status->setSummary('Stopping processes …');

            $process->stop();

        } elseif ($process->isTerminated()) {
            if ($process->isSuccessful()) {
                $status->setStatus(TaskStatus::STATUS_COMPLETE);
            } else {
                $status->setStatus(TaskStatus::STATUS_ERROR);
            }

            $this->setStatusLabels($status, $this->translator);

        } elseif (!$process->isStarted()) {
            $process->start();
        }

        return $status;
    }

    /**
     * @param TaskConfig $config
     *
     * @return TaskStatus
     */
    public function stop(TaskConfig $config)
    {
        $config->setStatus('stopping');

        return $this->update($config);
    }

    /**
     * @param TaskConfig $config
     *
     * @return TaskStatus
     */
    public function delete(TaskConfig $config)
    {
        $status = $this->stop($config);

        if (!$status->isActive()) {
            $this->getProcess($config)->delete();
        }

        return $status;
    }

    /**
     * @return TaskStatus
     */
    abstract protected function getInitialStatus(TaskConfig $config);

    /**
     * @return Process|ProcessController
     */
    abstract protected function getProcess(TaskConfig $config);
}
