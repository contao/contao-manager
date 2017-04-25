<?php

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

    /**
     * @var array
     */
    private $headers;

    /**
     * Constructor.
     *
     * @param ApiProblem      $problem
     * @param \Exception|null $previous
     * @param array           $headers
     * @param int             $code
     */
    public function __construct(ApiProblem $problem, \Exception $previous = null, array $headers = [], $code = 0)
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
     *
     * @return ApiProblem
     */
    public function getApiProblem()
    {
        return $this->problem;
    }
}
