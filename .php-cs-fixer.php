<?php

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12'                      => true,
        'array_syntax'                => ['syntax' => 'short'],
        'ordered_imports'             => true,
        'no_unused_imports'           => true,
        'trailing_comma_in_multiline' => true,
        'binary_operator_spaces'      => [
            'operators' => [
                '=>' => 'align_single_space_minimal',
            ],
        ],
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()->in(__DIR__)
    );
