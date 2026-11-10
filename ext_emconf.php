<?php

// Only needed for installations without Composer (classic mode).
$EM_CONF[$_EXTKEY] = [
    'title' => 'DRK Dienstabende',
    'description' => 'Inhaltselement „Dienstabende“: zeigt Termine aus einer JSON-Adresse als Akkordeon.',
    'category' => 'plugin',
    'state' => 'beta',
    'version' => '0.1.2',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
            'fluid_styled_content' => '12.4.0-13.4.99',
        ],
    ],
    'autoload' => [
        'psr-4' => ['Rubeen\\DrkDienstabende\\' => 'Classes/'],
    ],
];
