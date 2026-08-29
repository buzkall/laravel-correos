<?php

use Pest\Rector\Set\PestSetList;
use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;
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

        // The package does not use strict types; adding them is a behavioural
        // change that is out of scope for the tooling set-up.
        SafeDeclareStrictTypesRector::class,
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
