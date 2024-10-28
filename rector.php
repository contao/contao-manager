<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withSets([
        LevelSetList::UP_TO_PHP_81,
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,

        // https://getrector.com/blog/5-common-mistakes-in-rector-config-and-how-to-avoid-them
        SetList::DEAD_CODE,
        //SetList::CODE_QUALITY,
        //SetList::CODING_STYLE,
        //SetList::NAMING,
        //SetList::TYPE_DECLARATION,
        //SetList::PRIVATIZATION,
        //SetList::EARLY_RETURN,
        //SetList::INSTANCEOF,
    ])
    ->withPaths([
        __DIR__.'/api',
        __DIR__.'/public',
    ])
    ->withSkipPath(__DIR__.'/downgrade.php')
    ->withRootFiles()
    ->withParallel()
    ->withCache(sys_get_temp_dir().'/rector/contao-manager')
;
