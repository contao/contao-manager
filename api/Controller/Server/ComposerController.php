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
use Contao\ManagerApi\I18n\Translator;
use Contao\ManagerApi\System\ServerInfo;
use Crell\ApiProblem\ApiProblem;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Exception\ValidationException;
use JsonSchema\Validator;
use Seld\JsonLint\ParsingException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/server/composer", methods={"GET"})
 */
class ComposerController
{

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var Translator
     */
    private $translator;

    public function __construct(Environment $environment, Translator $translator)
    {
        $this->environment = $environment;
        $this->translator = $translator;
    }

    public function __invoke(ManagerConfig $managerConfig, ServerInfo $serverInfo): Response
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
            'vendor' => ['found' => is_dir($this->environment->getVendorDir())],
        ];

        if (($errors = $this->validateLockFile($result)) !== null) {
            $result = $errors;
        } elseif (($errors = $this->validateSchema($result)) !== null) {
            $result = $errors;
        }

        return new JsonResponse($result);
    }

    private function validateSchema(array $result): ?array
    {
        try {
            $schemaFile = __DIR__.'/../../../vendor/composer/composer/res/composer-schema.json';

            // Prepend with file:// only when not using a special schema already (e.g. in the phar)
            if (false === strpos($schemaFile, '://')) {
                $schemaFile = 'file://'.$schemaFile;
            }

            $schema = (object) ['$ref' => $schemaFile];
            $schema->required = [];

            $value = json_decode(file_get_contents($this->environment->getJsonFile()), false);
            $validator = new Validator();
            $validator->validate($value, $schema, Constraint::CHECK_MODE_EXCEPTIONS);

            return null;
        } catch (ValidationException $e) {
            $result['json']['valid'] = false;
            $result['json']['error'] = $this->translator->trans('boot.composer.invalid', ['exception' => $e->getMessage()]);

            return $result;
        }
    }

    private function validateLockFile(array $result): ?array
    {
        try {
            $locker = $this->environment->getComposer()->getLocker();

            if ($locker->isLocked()) {
                $result['lock']['found'] = true;

                if ($locker->isFresh()) {
                    $result['lock']['fresh'] = true;
                }
            }

            return null;
        } catch (\InvalidArgumentException $e) {
            $result['json']['found'] = false;
            $result['json']['valid'] = false;
        } catch (ParsingException $e) {
            $result['json']['valid'] = false;
            $result['json']['error'] = $this->translator->trans('boot.composer.invalid', ['exception' => $e->getMessage().' '.$e->getDetails()]);
        }

        return $result;
    }
}
