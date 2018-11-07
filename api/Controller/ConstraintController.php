<?php

namespace Contao\ManagerApi\Controller;

use Composer\Semver\VersionParser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ConstraintController
{
    public function __invoke(Request $request)
    {
        if (!$request->request->has('constraint')) {
            return new Response('Missing constraint in POST data.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $versionParser = new VersionParser();
            $versionParser->parseConstraints($request->request->get('constraint'));
        } catch (\Exception $exception) {
            return new JsonResponse(['valid' => false, 'error'  => $exception->getMessage()]);
        }

        return new JsonResponse(['valid' => true, 'error' => null]);
    }
}
