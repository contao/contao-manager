<?php

namespace Contao\ManagerApi\Controller;

use Composer\Semver\VersionParser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ConstraintController
{
    public function __invoke(Request $request)
    {
        if (!$request->request->has('constraint')) {
            return new JsonResponse(
                [
                    'status' => 'ERROR',
                    'error'  => 'invalid payload'
                ],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $versionParser = new VersionParser();

        try {
            $versionParser->parseConstraints($request->request->get('constraint'));
        } catch (\Exception $exception) {
            return new JsonResponse(
                [
                    'status' => 'ERROR',
                    'error'  => $exception->getMessage()
                ],
                JsonResponse::HTTP_OK
            );
        }

        return new JsonResponse(['status' => 'OK']);
    }
}
