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

    public function __construct(string $message, int $code, string $responseBody, string $requestBody = null)
    {
        parent::__construct($message, $code);

        $this->responseBody = $responseBody;
        $this->requestBody = $requestBody;
    }

    /**
     * Returns the response status code or general error 500.
     */
    public function getStatusCode(): int
    {
        return $this->getCode();
    }

    /**
     * Returns the Cloud error message or the exception message as fallback.
     */
    public function getErrorMessage(): string
    {
        $message = $this->getMessage()."\n\nResponse:\n".$this->responseBody;

        if ($this->requestBody) {
            $message .= "\n\nRequest:\n".$this->requestBody;
        }

        return $message;
    }
}
