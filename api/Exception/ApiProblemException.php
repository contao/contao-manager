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

use Crell\ApiProblem\ApiProblem;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Exception containing an API problem.
 */
class ApiProblemException extends HttpException
{
    /**
     * @var ApiProblem
     */
    private $problem;

    public function __construct(ApiProblem $problem, \Throwable $previous = null, array $headers = [], int $code = 0)
    {
        $this->problem = $problem;

        parent::__construct(
            $problem->getStatus(),
            $problem->getTitle(),
            $previous,
            $headers,
            $code
        );
    }

    /**
     * Gets the API problem.
     */
    public function getApiProblem(): ApiProblem
    {
        return $this->problem;
    }
}
