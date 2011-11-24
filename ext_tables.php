<?php
    
if (!defined ('TYPO3_MODE')) die ('Access denied.');

t3lib_extMgm::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:socialpostd/res/typoscript/tsconfig.txt">');

$tempColumns = array (
    'tx_socialpostd_fb_publish' => array (        
        'exclude' => 0,        
        'label' => 'LLL:EXT:socialpostd/locallang_db.xml:tt_news.tx_socialpostd_fb_publish',        
        'config' => array (
            'type' => 'check',
        )
     ),
    'tx_socialpostd_fb_ignor_publish' => array ( 
        'label' => 'LLL:EXT:socialpostd/locallang_db.xml:tt_news.tx_socialpostd_fb_ignor_publish',        
        'config' => array (
            'type' => 'check',
        )
     ),
    'tx_socialpostd_tw_publish' => array ( 
        'label' => 'LLL:EXT:socialpostd/locallang_db.xml:tt_news.tx_socialpostd_tw_publish',        
        'config' => array (
            'type' => 'check',
        )
     ),
    'tx_socialpostd_tw_ignor_publish' => array ( 
        'label' => 'LLL:EXT:socialpostd/locallang_db.xml:tt_news.tx_socialpostd_tw_ignor_publish',        
        'config' => array (
            'type' => 'check',
        )
    ),
);

t3lib_div::loadTCA('tt_news');
t3lib_extMgm::addTCAcolumns('tt_news', $tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('tt_news', 'tx_socialpostd_fb_publish,tx_socialpostd_fb_ignor_publish,tx_socialpostd_tw_publish,tx_socialpostd_tw_ignor_publish;;;;1-1-1');

?>