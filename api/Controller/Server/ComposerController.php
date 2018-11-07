<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller\Server;

use Composer\Factory;
use Composer\IO\NullIO;
use Contao\ManagerApi\Composer\Environment;
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Crell\ApiProblem\ApiProblem;
use Seld\JsonLint\ParsingException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class ComposerController extends Controller
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * Constructor.
     *
     * @param KernelInterface $kernel
     */
    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * Gets response about Composer configuration and file validation.
     *
     * @return Response
     */
    public function __invoke()
    {
        if (!$this->get('contao_manager.config.manager')->has('server')
            || !$this->get('contao_manager.system.server_info')->getPhpExecutable()
        ) {
            return new ApiProblemResponse(
                (new ApiProblem('Missing hosting configuration.', '/api/server/config'))
                    ->setStatus(Response::HTTP_SERVICE_UNAVAILABLE)
            );
        }

        $result = [
            'json' => ['found' => true, 'valid' => true, 'error' => null],
            'lock' => ['found' => false, 'fresh' => false],
            'vendor' => ['found' => is_dir($this->environment->getVendorDir())],
        ];

        try {
            $composer = Factory::create(new NullIO(), $this->environment->getJsonFile(), true);
            $locker = $composer->getLocker();

            if ($locker->isLocked()) {
                $result['lock']['found'] = true;

                if ($locker->isFresh()) {
                    $result['lock']['fresh'] = true;
                }
            }
        } catch (\InvalidArgumentException $e) {
            $result['json']['found'] = false;
            $result['json']['valid'] = false;
        } catch (ParsingException $e) {
            $result['json']['valid'] = false;
            $result['json']['error'] = [
                'message' => $e->getMessage(),
                'details' => $e->getDetails(),
            ];
        }

        return new JsonResponse($result);
    }
}
