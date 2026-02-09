<?php

declare(strict_types=1);

use Contao\EasyCodingStandard\Set\SetList;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use PhpCsFixer\Fixer\FunctionNotation\UseArrowFunctionsFixer;
use PhpCsFixer\Fixer\Operator\NoUselessConcatOperatorFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Option;

return ECSConfig::configure()
    ->withSets([SetList::CONTAO])
    ->withPaths([
        __DIR__.'/api',
        __DIR__.'/public',
    ])
    ->withSkip([
        __DIR__.'/downgrade.php',
        __DIR__.'/installer.php',
        __DIR__.'/stub.php',
        HeaderCommentFixer::class => [
            __DIR__.'/ecs.php',
            __DIR__.'/rector.php',
            __DIR__.'/scoper.inc.php',
        ],
        UseArrowFunctionsFixer::class => [__DIR__.'/scoper.inc.php'],
        NoUselessConcatOperatorFixer::class => [__DIR__.'/api/ApiKernel.php'],
    ])
    ->withRootFiles()
    ->withParallel()
    ->withSpacing(Option::INDENTATION_SPACES, "\n")
    ->withConfiguredRule(HeaderCommentFixer::class, ['header' => "This file is part of Contao Manager.\n\n(c) Contao Association\n\n@license LGPL-3.0-or-later"])
    ->withCache(sys_get_temp_dir().'/ecs/contao-manager')
;
