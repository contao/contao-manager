<?php

namespace Contao\ManagerApi\EventListener;

use Contao\ManagerApi\Exception\ApiProblemException;
use Crell\ApiProblem\ApiProblem;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if ($exception instanceof ApiProblemException) {
            $problem = $exception->getApiProblem();
        } else {
            $problem = $this->convertException($exception);
        }

        if (!$problem->getStatus()) {
            $problem->setStatus(500);
        }

        $response = new Response(
            $problem->asJson(),
            $problem->getStatus(),
            ['Content-Type' => 'application/problem+json']
        );

        $event->setResponse($response);
    }

    private function convertException(\Exception $exception)
    {
        $problem = new ApiProblem($exception->getMessage());

        if ($exception instanceof HttpExceptionInterface) {
            $problem->setStatus($exception->getStatusCode());
        } else {
            $problem->setStatus(500);
            $problem->setDetail($exception->getTraceAsString());
        }

        return $problem;
    }
}
