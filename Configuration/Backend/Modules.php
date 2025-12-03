<?php

use In2code\Alternative\Controller\ModuleController;

return [
    'alternative_Module' => [
        'parent' => 'file',
        'position' => ['bottom'],
        'access' => 'user',
        'iconIdentifier' => 'EXT:alternative/Resources/Public/Icons/Extension.svg',
        'path' => '/module/alternative/module',
        'labels' => 'LLL:EXT:alternative/Resources/Private/Language/Backend/locallang.xlf:button.translate',
        'extensionName' => 'Alternative',
        'controllerActions' => [
            ModuleController::class => [
                'addMetadata',
            ],
        ],
    ],
];
