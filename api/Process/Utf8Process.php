<?php

namespace Contao\ManagerApi\Process;

use Symfony\Component\Process\Process;

class Utf8Process extends Process
{
    /**
     * {@inheritdoc}
     */
    public function getOutput()
    {
        return $this->convertEncoding(parent::getOutput());
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorOutput()
    {
        return $this->convertEncoding(parent::getErrorOutput());
    }

    private function convertEncoding(string $data)
    {
        if (false !== @json_encode($data)) {
            return $data;
        }

        if (function_exists('mb_convert_encoding')) {
            $encoding = null;

            if (function_exists('mb_detect_encoding')) {
                $encoding = mb_detect_encoding($data) ?: null;
            }

            return mb_convert_encoding($data, 'UTF-8', $encoding);
        }

        if (function_exists('utf8_encode')) {
            return utf8_encode($data);
        }

        return $data;
    }
}
