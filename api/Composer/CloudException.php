<?php

namespace Contao\ManagerApi\Composer;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

class CloudException extends \RuntimeException
{
    /**
     * @var RequestException
     */
    private $exception;

    /**
     * @inheritDoc
     */
    public function __construct(RequestException $exception)
    {
        $this->exception = $exception;

        parent::__construct($exception->getMessage(), $exception->getCode(), $exception);
    }

    /**
     * Returns the response status code or general error 500.
     *
     * @return int
     */
    public function getStatusCode()
    {
        if (($response = $this->exception->getResponse()) instanceof ResponseInterface) {
            return $response->getStatusCode();
        }

        return 500;
    }

    /**
     * Returns the Cloud error message or the exception message as fallback.
     *
     * @return string
     */
    public function getErrorMessage()
    {
        if (($response = $this->exception->getResponse()) instanceof ResponseInterface) {
            try {
                $json = \GuzzleHttp\json_decode($response->getBody(), true);

                if (array_key_exists('msg', $json)) {
                    return $json['msg'];
                }
            } catch (\InvalidArgumentException $e) {
                // do nothing
            }
        }

        return $this->exception->getMessage();
    }
}
