<?php

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
    public function __construct($filename, $content = '', $code = 0, \Exception $previous = null)
    {
        parent::__construct(sprintf('File "%s" does not contain valid JSON.', $filename), $code, $previous);

        $this->filename = $filename;
        $this->content = $content;

        $this->jsonError = json_last_error();
        $this->jsonErrorMsg = json_last_error_msg();
    }

    /**
     * Gets name of the JSON file.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Gets the invalid file content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Gets the json_last_error.
     *
     * @return string
     */
    public function getJsonError()
    {
        return $this->jsonError;
    }

    /**
     * Gets the json_last_error_msg.
     *
     * @return string
     */
    public function getJsonErrorMessage()
    {
        return $this->jsonErrorMsg;
    }
}
