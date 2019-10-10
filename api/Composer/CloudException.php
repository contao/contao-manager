<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Composer;

class CloudException extends \RuntimeException
{
    /**
     * @var string
     */
    private $responseBody;

    /**
     * @var string|null
     */
    private $requestBody;

    /**
     * {@inheritdoc}
     */
    public function __construct($message, $code, $responseBody, $requestBody = null)
    {
        parent::__construct($message, $code);

        $this->responseBody = $responseBody;
        $this->requestBody = $requestBody;
    }

    /**
     * Returns the response status code or general error 500.
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->getCode();
    }

    /**
     * Returns the Cloud error message or the exception message as fallback.
     *
     * @return string
     */
    public function getErrorMessage()
    {
        $message = $this->getMessage()."\n\nResponse:\n".$this->responseBody;

        if ($this->requestBody) {
            $message .= "\n\nRequest:\n".$this->requestBody;
        }

        return $message;
    }
}
