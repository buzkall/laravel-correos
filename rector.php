<?php

declare(strict_types=1);

use Pest\Rector\Set\PestSetList;
use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/config',
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withRootFiles()
    ->withSkip([
        __DIR__.'/tests/Fixtures',
    ])
    ->withImportNames(removeUnusedImports: true)
    ->withPhpSets(php84: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        instanceOf: true,
        earlyReturn: true,
    )
    ->withSets([
        // Version-agnostic Laravel clean-ups only: the package still supports
        // Laravel 11, 12 and 13, so no version upgrade set is applied here.
        LaravelSetList::LARAVEL_CODE_QUALITY,
        PestSetList::CODING_STYLE,
    ]);
