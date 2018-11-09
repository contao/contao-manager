<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\IntegrityCheck;

use Crell\ApiProblem\ApiProblem;

class MemoryLimitCheck extends AbstractIntegrityCheck implements CliIntegrityCheckInterface
{
    /**
     * {@inheritdoc}
     */
    public function run()
    {
        if (PHP_SAPI !== 'cli' || $this->hasEnoughMemory()) {
            return null;
        }

        return (new ApiProblem(
            $this->trans('memory_limit.title'),
            'https://php.net/memory_limit'
        ))->setDetail($this->trans('memory_limit.detail', ['limit' => trim(ini_get('memory_limit'))]));
    }

    private function hasEnoughMemory()
    {
        $memoryLimit = trim(ini_get('memory_limit'));

        if ($memoryLimit == -1) {
            return true;
        }

        /** @noinspection SubStrUsedAsArrayAccessInspection */
        $unit = strtolower(substr($memoryLimit, -1, 1));
        $memoryLimit = (int) $memoryLimit;

        switch ($unit) {
            /* @noinspection PhpMissingBreakStatementInspection */
            case 'g':
                $memoryLimit *= 1024;
                // no break (cumulative multiplier)
            /* @noinspection PhpMissingBreakStatementInspection */
            case 'm':
                $memoryLimit *= 1024;
                // no break (cumulative multiplier)
            case 'k':
                $memoryLimit *= 1024;
        }

        return $memoryLimit >= 1024 * 1024 * 1536;
    }
}
