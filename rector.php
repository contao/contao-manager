<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()
    ->withSets([
        LevelSetList::UP_TO_PHP_81,

        // https://getrector.com/blog/5-common-mistakes-in-rector-config-and-how-to-avoid-them
        SetList::DEAD_CODE,
        //SetList::CODE_QUALITY,
        //SetList::CODING_STYLE,
        //SetList::NAMING,
        //SetList::TYPE_DECLARATION,
        //SetList::PRIVATIZATION,
        //SetList::EARLY_RETURN,
        //SetList::INSTANCEOF,

        //SymfonySetList::SYMFONY_40,
        //SymfonySetList::SYMFONY_41,
        //SymfonySetList::SYMFONY_42,
        //SymfonySetList::SYMFONY_43,
        //SymfonySetList::SYMFONY_44,
        //SymfonySetList::SYMFONY_50,
        //SymfonySetList::SYMFONY_50_TYPES,
        //SymfonySetList::SYMFONY_51,
        //SymfonySetList::SYMFONY_52,
        //SymfonySetList::SYMFONY_53,
        //SymfonySetList::SYMFONY_54,
        //SymfonySetList::SYMFONY_60,
        //SymfonySetList::SYMFONY_61,
        //SymfonySetList::SYMFONY_62,
        //SymfonySetList::SYMFONY_63,
        //SymfonySetList::SYMFONY_64,
        //SymfonySetList::SYMFONY_CODE_QUALITY,
        //SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
    ])
    ->withPaths([
        __DIR__.'/api',
        __DIR__.'/public',
    ])
    ->withSkip([
        __DIR__.'/downgrade.php',
        __DIR__.'/stub.php',
        RemoveAlwaysTrueIfConditionRector::class => [__DIR__.'/api/TaskOperation/AbstractInlineOperation.php'],
        RemoveUnusedPrivateMethodParameterRector::class => [__DIR__.'/api/TaskOperation/Contao/CreateContaoOperation.php'],
    ])
    ->withRootFiles()
    ->withParallel()
    ->withCache(sys_get_temp_dir().'/rector/contao-manager')
;
