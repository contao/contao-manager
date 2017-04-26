<?php

/*
 * This file is part of Contao Manager.
 *
 * Copyright (c) 2016-2017 Contao Association
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerApi\EventListener;

use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ExceptionListener
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var bool
     */
    private $debug;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     * @param bool            $debug
     */
    public function __construct(LoggerInterface $logger, $debug = false)
    {
        $this->logger = $logger;
        $this->debug = $debug;
    }

    /**
     * Responds with application/problem+json on kernel.exception.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (!in_array('application/json', $event->getRequest()->getAcceptableContentTypes(), true)) {
            return;
        }

        $exception = $this->convertException($event->getException());

        $this->logException($exception);

        $event->setResponse(
            ApiProblemResponse::createFromException(
                $exception,
                $this->debug
            )
        );
    }

    /**
     * Logs the exception if a logger is available.
     *
     * @param \Exception $exception
     */
    private function logException(\Exception $exception)
    {
        if (null === $this->logger) {
            return;
        }

        $message = sprintf(
            'Uncaught PHP Exception %s: "%s" at %s line %s',
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );

        if (!$exception instanceof HttpExceptionInterface || $exception->getStatusCode() >= 500) {
            $this->logger->critical($message, ['exception' => $exception]);
        } else {
            $this->logger->error($message, ['exception' => $exception]);
        }
    }

    /**
     * Tries to convert known exceptions to a HttpException.
     *
     * @param \Exception $exception
     *
     * @return \Exception
     */
    private function convertException(\Exception $exception)
    {
        switch (true) {
            case $exception instanceof AccessDeniedException:
            case $exception instanceof AuthenticationException:
                return new AccessDeniedHttpException($exception->getMessage(), $exception);
        }

        return $exception;
    }
}
