<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2018 Contao Association
 *
 * @license LGPL-3.0+
 */

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
