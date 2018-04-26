<?php

namespace Contao\ManagerApi\Task;

interface TaskInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @param TaskConfig $config
     *
     * @return TaskStatus
     */
    public function create(TaskConfig $config);

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
