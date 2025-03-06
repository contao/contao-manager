<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->addPathToScan('./api', false)

    // required by WebauthnSerializerFactory
    ->ignoreErrorsOnPackage('phpdocumentor/reflection-docblock', [ErrorType::UNUSED_DEPENDENCY])

    ->ignoreErrorsOnExtensionAndPath('ext-intl', 'api/Command/AboutCommand.php', [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnExtensionAndPath('ext-pcntl', 'api/Process/ProcessRunner.php', [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnExtensionAndPath('ext-mbstring', 'api/Process/Utf8Process.php', [ErrorType::SHADOW_DEPENDENCY])
;
