<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('*/vendor/*')
    ->exclude('node_modules')
    ->in(__DIR__)
    ->notPath('lib/system/api');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@PER-CS' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => false,
        ],
        'ordered_imports' => [
            'imports_order' => ['class', 'function', 'const'],
        ],
    ])
    ->setFinder($finder);
