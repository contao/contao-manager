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

class RequestException extends \RuntimeException
{
    public function __construct(
        private readonly string $url,
        private readonly int|null $statusCode,
        \Throwable|null $previous = null,
    ) {
        $message = \sprintf('HTTP request to %s failed ', $this->url);

        if (null !== $this->statusCode) {
            $message .= 'with status code '.$this->statusCode;
        }

        if (null !== $previous) {
            $message .= ' ('.$previous->getMessage().')';
        }

        parent::__construct(
            $message,
            $previous?->getCode() ?? 0,
            $previous,
        );
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
