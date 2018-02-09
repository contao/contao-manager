<?php

namespace Contao\ManagerApi\Task;

use Symfony\Component\Process\Process;
use Terminal42\BackgroundProcess\ProcessController;

abstract class AbstractProcessTask extends AbstractTask
{
    /**
     * @param TaskConfig $config
     *
     * @return TaskStatus
     */
    public function update(TaskConfig $config)
    {
        $status = $this->getInitialStatus($config);

        $process = $this->getProcess($config);

        $status->setDetail($process->getCommandLine());
        $status->setConsole($process->getOutput());

        if ('stopping' === $config->getStatus()) {

            if (!$process->isRunning()) {
                $status->setStatus(TaskStatus::STATUS_STOPPED);

                return $status;
            }

            $status->setSummary('Stopping processes …');

            $process->stop();

        } elseif ($process->isTerminated()) {

            if ($process->isSuccessful()) {
                $status->setSummary('Process successfull.');
                $status->setStatus(TaskStatus::STATUS_COMPLETE);
            } else {
                $status->setSummary('Process failed.');
                $status->setStatus(TaskStatus::STATUS_ERROR);
            }

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
