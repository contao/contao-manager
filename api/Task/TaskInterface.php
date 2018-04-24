<?php

namespace Contao\ManagerApi\Task;

interface TaskInterface
{
    /**
     * @param TaskConfig $config
     *
     * @return TaskStatus
     */
    public function update(TaskConfig $config);

    /**
     * @param TaskConfig $config
     *
     * @return TaskStatus
     */
    public function abort(TaskConfig $config);

    /**
     * @param TaskConfig $config
     *
     * @return TaskStatus
     */
    public function delete(TaskConfig $config);
}
