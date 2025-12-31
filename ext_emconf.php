<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Alternative - AI generated metatags for images in TYPO3',
    'description' => 'Automatically set alternative, title and description labels for images',
    'category' => 'plugin',
    'version' => '2.0.0',
    'author' => 'Alex Kellner',
    'author_email' => 'alexander.kellner@in2code.de',
    'author_company' => 'in2code.de',
    'state' => 'stable',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.9.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
