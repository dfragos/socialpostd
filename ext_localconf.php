<?php

if (!defined ('TYPO3_MODE')) die ('Access denied.');

if (t3lib_extMgm::isLoaded('scheduler')) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_socialpostd_scheduler'] = array (
		'extension'        => $_EXTKEY,
		'title'            => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xlf:scheduler.tx_socialpostd_title',
		'description'      => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xlf:scheduler.tx_socialpostd_description',
		'additionalFields' => ''
	);
}

?>