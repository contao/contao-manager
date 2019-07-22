<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller\Server;

use Contao\ManagerApi\Composer\Environment;
use Contao\ManagerApi\Config\ManagerConfig;
use Contao\ManagerApi\HttpKernel\ApiProblemResponse;
use Contao\ManagerApi\System\ServerInfo;
use Crell\ApiProblem\ApiProblem;
use Seld\JsonLint\ParsingException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/server/composer", methods={"GET"})
 */
class ComposerController
{
    public function __invoke(Environment $environment, ManagerConfig $managerConfig, ServerInfo $serverInfo): Response
    {
        if (!$managerConfig->has('server') || !$serverInfo->getPhpExecutable()) {
            return new ApiProblemResponse(
                (new ApiProblem('Missing hosting configuration.', '/api/server/config'))
                    ->setStatus(Response::HTTP_SERVICE_UNAVAILABLE)
            );
        }

        $result = [
            'json' => ['found' => true, 'valid' => true, 'error' => null],
            'lock' => ['found' => false, 'fresh' => false],
            'vendor' => ['found' => is_dir($environment->getVendorDir())],
        ];

        try {
            $locker = $environment->getComposer()->getLocker();

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
