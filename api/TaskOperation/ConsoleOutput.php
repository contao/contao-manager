<?php

namespace Contao\ManagerApi\TaskOperation;

class ConsoleOutput
{
    /**
     * @var string
     */
    private $output = '';

    public function __toString()
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
