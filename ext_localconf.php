<?php

defined('TYPO3') or die();

// File backend: no database tables needed. Group "pages": "Clear frontend caches" also refetches the JSON.
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['drk_dienstabende'] ??= [
    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
    'backend' => \TYPO3\CMS\Core\Cache\Backend\FileBackend::class,
    'groups' => ['pages'],
];

// Added right after the content rendering (fluid_styled_content or the site package), so no TypoScript include is needed.
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript('drk_dienstabende', 'setup', '
tt_content.drkdienstabende_list = COA_INT
tt_content.drkdienstabende_list.10 =< lib.contentElement
tt_content.drkdienstabende_list.10 {
    templateName = Dienstabende
    templateRootPaths.1700 = EXT:drk_dienstabende/Resources/Private/Templates/
    dataProcessing.1700 = Rubeen\DrkDienstabende\DataProcessing\EventsProcessor
}
', 'defaultContentRendering');
