<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::addPlugin(
    [
        'label' => 'Dienstabende',
        'description' => 'Termine aus einer JSON-Adresse als aufklappbare Liste',
        'value' => 'drkdienstabende_list',
        'icon' => 'content-accordion',
        'group' => 'plugins',
    ],
    'CType',
    'drk_dienstabende'
);

$GLOBALS['TCA']['tt_content']['types']['drkdienstabende_list'] = [
    'showitem' => '
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
            --palette--;;general,
            --palette--;;headers,
            bodytext;Einleitung (optional),
            pi_flexform;Termine,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
            --palette--;;frames,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
            --palette--;;hidden,
            --palette--;;access,
    ',
    'columnsOverrides' => [
        'bodytext' => ['config' => ['enableRichtext' => true]],
    ],
];

ExtensionManagementUtility::addPiFlexFormValue('*', 'FILE:EXT:drk_dienstabende/Configuration/FlexForms/Dienstabende.xml', 'drkdienstabende_list');
