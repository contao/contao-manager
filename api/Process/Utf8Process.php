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

use Symfony\Component\Process\Process;

class Utf8Process extends Process
{
    public function getOutput(): string
    {
        return $this->normalizeOutput(parent::getOutput());
    }

    public function getErrorOutput(): string
    {
        return $this->normalizeOutput(parent::getErrorOutput());
    }

    /**
     * Normalize encoding and try to fix PHP error level issues.
     */
    private function normalizeOutput(string $output): string
    {
        $output = $this->convertEncoding($output);

        return implode("\n", array_filter(
            preg_split('/\r\n|\r|\n/', $output),
            static function ($line) {
                return 0 !== strpos($line, 'PHP Warning:')
                    && 0 !== strpos($line, 'Warning:')
                    && 0 !== strpos($line, 'Deprecated:')
                    && 0 !== strpos($line, 'Runtime Notice:')
                    && 0 !== strpos($line, 'Failed loading ');
            }
        ));
    }

    private function convertEncoding(string $data): string
    {
        if (false !== @json_encode($data)) {
            return $data;
        }

        if (\function_exists('mb_convert_encoding')) {
            $encoding = null;

            if (\function_exists('mb_detect_encoding')) {
                $encoding = mb_detect_encoding($data, mb_detect_order(), true) ?: null;
            }

            return mb_convert_encoding($data, 'UTF-8', $encoding);
        }

        if (\function_exists('utf8_encode')) {
            return utf8_encode($data);
        }

        return $data;
    }
}
