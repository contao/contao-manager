<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\TaskOperation;

class ConsoleOutput implements \Stringable
{
    private string $output = '';

    public function __toString(): string
    {
        return $this->output;
    }

    /**
     * Adds output to the console log.
     */
    public function add(string $output, string $title = null): self
    {
        if (null !== $title) {
            $output = sprintf("%s\n\n%s", $title, $output);
        }

        if (!$output) {
            return $this;
        }

        if ($this->output) {
            $output = $this->output."\n\n".$output;
        }

        $this->output = $output;

        return $this;
    }
}
