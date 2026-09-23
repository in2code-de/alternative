<?php

use In2code\Alternative\Controller\ModuleController;
use In2code\Alternative\Controller\SettingsModuleController;
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * The module "in2code_ai" is defined identically in all in2code AI extensions (alternative, imager, sitescore, texter),
 * so each extension can be installed standalone while all of them share one "AI" module in the backend:
 * - TYPO3 13: main module "AI" in the module menu, one entry per extension below
 * - TYPO3 14: module "Admin Tools > AI" with a card based overview of the extensions (showSubmoduleOverview)
 * Keep this definition in sync with the other extensions.
 */
$isTypo3Version14 = (new Typo3Version())->getMajorVersion() >= 14;

return [
    'in2code_ai' => $isTypo3Version14
        ? [
            'parent' => 'admin',
            'access' => 'user',
            'path' => '/module/in2code-ai',
            'iconIdentifier' => 'actions-lightbulb-on',
            'labels' => [
                'title' => 'LLL:EXT:alternative/Resources/Private/Language/Backend/locallang_module.xlf:mainmodule.title',
            ],
            'appearance' => [
                'dependsOnSubmodules' => true,
            ],
            'showSubmoduleOverview' => true,
        ]
        : [
            'labels' => [
                'title' => 'LLL:EXT:alternative/Resources/Private/Language/Backend/locallang_module.xlf:mainmodule.title',
            ],
            'iconIdentifier' => 'actions-lightbulb-on',
            'position' => ['after' => 'file'],
        ],
    'in2code_ai_alternative' => [
        'parent' => 'in2code_ai',
        'access' => 'user',
        'path' => '/module/in2code-ai/alternative',
        'icon' => 'EXT:alternative/Resources/Public/Icons/Extension.svg',
        'labels' => [
            'title' => 'LLL:EXT:alternative/Resources/Private/Language/Backend/locallang_module.xlf:module.title',
            'description' => 'LLL:EXT:alternative/Resources/Private/Language/Backend/locallang_module.xlf:module.description',
            'shortDescription' => 'LLL:EXT:alternative/Resources/Private/Language/Backend/locallang_module.xlf:module.shortDescription',
        ],
        'routes' => [
            '_default' => [
                'target' => SettingsModuleController::class . '::indexAction',
            ],
            'save' => [
                'target' => SettingsModuleController::class . '::saveAction',
                'methods' => ['POST'],
            ],
        ],
    ],
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
                'addMetadataFromFolder',
            ],
        ],
    ],
];
