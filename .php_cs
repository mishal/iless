<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__ . '/lib')
    ->in(__DIR__ . '/bin')
    ->in(__DIR__ . '/examples')
    ->in(__DIR__ . '/tests');

return Symfony\CS\Config\Config::create()
    ->fixers([
        'psr1',
        'psr2',
        'symfony',
        'short_array_syntax',
        'multiline_spaces_before_semicolon',
        'concat_with_spaces', // spaces
        'encoding',
        '-phpdoc_params', // do not align parameters in doc blocks
        '-align_double_arrow',
        '-align_equals',
    ])
    ->finder($finder);
