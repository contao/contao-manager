<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\EventListener;

use Contao\ManagerApi\Exception\ApiProblemException;
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ExceptionListener implements EventSubscriberInterface
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
     * @param bool $debug
     */
    public function __construct(LoggerInterface $logger, $debug = false)
    {
        $this->logger = $logger;
        $this->debug = $debug;
    }

    /**
     * Responds with application/problem+json on kernel.exception.
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        if (!\in_array('application/json', $event->getRequest()->getAcceptableContentTypes(), true)) {
            return;
        }

        Response::closeOutputBuffers(0, false);

        $exception = $this->convertException($event->getThrowable());

        $this->logException($exception);

        if ($exception instanceof HttpExceptionInterface && !$exception instanceof ApiProblemException) {
            $response = new Response($exception->getMessage(), $exception->getStatusCode(), $exception->getHeaders());
        } else {
            $response = ApiProblemResponse::createFromException($exception, $this->debug);
        }

        $event->setResponse($response);
    }

    public static function getSubscribedEvents(): array
    {
        return ['kernel.exception' => ['onKernelException', 10]];
    }

    /**
     * Logs the exception if a logger is available.
     */
    private function logException(\Throwable $exception): void
    {
        if (null === $this->logger) {
            return;
        }

        $message = sprintf(
            'Uncaught PHP Exception %s: "%s" at %s line %s',
            \get_class($exception),
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
     */
    private function convertException(\Throwable $exception): \Throwable
    {
        switch (true) {
            case $exception instanceof AccessDeniedException:
            case $exception instanceof AuthenticationException:
                return new AccessDeniedHttpException($exception->getMessage(), $exception);
        }

        return $exception;
    }
}
