<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Exception;

class InvalidJsonException extends \InvalidArgumentException
{
    /**
     * @var string
     */
    private $filename;

    /**
     * @var int
     */
    private $jsonError;

    /**
     * @var string
     */
    private $jsonErrorMsg;

    /**
     * @var string
     */
    private $content;

    /**
     * Constructor.
     *
     * @param string     $filename
     * @param string     $content
     * @param int        $code
     * @param \Exception $previous
     */
    public function __construct(string $filename, string $content = '', int $code = 0, \Exception $previous = null)
    {
        parent::__construct(sprintf('File "%s" does not contain valid JSON.', $filename), $code, $previous);

        $this->filename = $filename;
        $this->content = $content;

        $this->jsonError = json_last_error();
        $this->jsonErrorMsg = json_last_error_msg();
    }

    /**
     * Gets name of the JSON file.
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Gets the invalid file content.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Gets the json_last_error.
     */
    public function getJsonError(): int
    {
        return $this->jsonError;
    }

    /**
     * Gets the json_last_error_msg.
     */
    public function getJsonErrorMessage(): string
    {
        return $this->jsonErrorMsg;
    }
}
