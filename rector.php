<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {

    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/rector.php',
    ]);

    $rectorConfig->sets([
        SetList::PHP_74,
        // Please no dead code or unneeded variables.
        SetList::DEAD_CODE,
        // Try to figure out type hints.
        SetList::TYPE_DECLARATION,
    ]);

    $rectorConfig->skip([
        // We like our tags.
        RemoveUselessParamTagRector::class,
        RemoveUselessReturnTagRector::class,
        RemoveUselessVarTagRector::class,
    ]);

    $rectorConfig->removeUnusedImports();
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);
};
