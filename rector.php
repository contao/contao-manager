<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Concat\JoinStringConcatRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\CodingStyle\Rector\String_\SymplifyQuoteEscapeRector;
use Rector\CodingStyle\Rector\String_\UseClassKeywordForClassNameResolutionRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Rector\EarlyReturn\Rector\If_\ChangeOrIfContinueToMultiContinueRector;
use Rector\EarlyReturn\Rector\Return_\ReturnBinaryOrToEarlyReturnRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withSets([
        LevelSetList::UP_TO_PHP_81,

        // https://getrector.com/blog/5-common-mistakes-in-rector-config-and-how-to-avoid-them
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::TYPE_DECLARATION,
        SetList::PRIVATIZATION,
        SetList::EARLY_RETURN,
        SetList::INSTANCEOF,
    ])
    ->withPaths([
        __DIR__.'/api',
        __DIR__.'/public',
    ])
    ->withSkip([
        __DIR__.'/downgrade.php',
        __DIR__.'/stub.php',
        ChangeOrIfContinueToMultiContinueRector::class,
        CatchExceptionNameMatchingTypeRector::class,
        ReturnBinaryOrToEarlyReturnRector::class,
        FlipTypeControlToUseExclusiveTypeRector::class,
        RemoveAlwaysTrueIfConditionRector::class => [
            __DIR__.'/api/Command/AboutCommand.php',
            __DIR__.'/api/Process/PhpExecutableFinder.php',
            __DIR__.'/api/TaskOperation/AbstractInlineOperation.php',
        ],
        SimplifyIfReturnBoolRector::class => [__DIR__.'/api/IntegrityCheck/GraphicsLibCheck.php'],
        RemoveUnusedPrivateMethodParameterRector::class => [__DIR__.'/api/TaskOperation/Contao/CreateContaoOperation.php'],
        JoinStringConcatRector::class => [__DIR__.'/api/ApiKernel.php'],
        ClosureToArrowFunctionRector::class => [__DIR__.'/scoper.inc.php'],
        EncapsedStringsToSprintfRector::class => [__DIR__.'/scoper.inc.php'],
        SymplifyQuoteEscapeRector::class => [__DIR__.'/scoper.inc.php'],
        UseClassKeywordForClassNameResolutionRector::class => [__DIR__.'/scoper.inc.php'],
    ])
    ->withRootFiles()
    ->withParallel()
    ->withCache(sys_get_temp_dir().'/rector/contao-manager')
;
