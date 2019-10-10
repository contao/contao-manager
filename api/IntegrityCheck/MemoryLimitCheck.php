<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\IntegrityCheck;

use Crell\ApiProblem\ApiProblem;

class MemoryLimitCheck extends AbstractIntegrityCheck
{
    /**
     * {@inheritdoc}
     */
    public function run(): ?ApiProblem
    {
        if (\PHP_SAPI !== 'cli' || $this->hasEnoughMemory()) {
            return null;
        }

        return (new ApiProblem(
            $this->trans('memory_limit.title'),
            'https://php.net/memory_limit'
        ))->setDetail($this->trans('memory_limit.detail', ['limit' => trim(ini_get('memory_limit'))]));
    }

    private function hasEnoughMemory(): bool
    {
        $memoryLimit = (string) trim(ini_get('memory_limit'));

        if ('-1' === $memoryLimit) {
            return true;
        }

        /** @noinspection SubStrUsedAsArrayAccessInspection */
        $unit = strtolower(substr($memoryLimit, -1, 1));
        $memoryLimit = (int) $memoryLimit;

        switch ($unit) {
            /* @noinspection PhpMissingBreakStatementInspection */
            case 'g':
                $memoryLimit *= 1024;
            /* @noinspection PhpMissingBreakStatementInspection */
            // no break
            case 'm':
                $memoryLimit *= 1024;
                // no break (cumulative multiplier)
            case 'k':
                $memoryLimit *= 1024;
        }

        return $memoryLimit >= 1024 * 1024 * 256;
    }
}
