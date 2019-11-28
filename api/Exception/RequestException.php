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

use Throwable;

class RequestException extends \RuntimeException
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var int
     */
    private $statusCode;

    public function __construct(string $url, int $statusCode, Throwable $previous = null)
    {
        $message = "HTTP request to $url failed with status code $statusCode";

        if ($previous) {
            $message .= ' ('.$previous->getMessage().')';
        }

        parent::__construct(
            $message,
            $previous ? $previous->getCode() : 0,
            $previous
        );

        $this->url = $url;
        $this->statusCode = $statusCode;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
