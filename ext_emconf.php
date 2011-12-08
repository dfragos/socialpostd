<?php

########################################################################
# Extension Manager/Repository config file for ext "socialpostd".
#
# Auto generated 06-12-2011 18:51
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Social Post',
	'description' => 'Publish news records from tt_news on Facebook and Twitter.',
	'category' => 'be',
	'shy' => 0,
	'version' => '1.6.0',
	'dependencies' => 'tt_news,scheduler',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Dionysios Fragkopoulos',
	'author_email' => 'me@dfragos.me',
	'author_company' => 'www.dfragos.me',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'tt_news' => '',
			'scheduler' => '',
			'typo3' => '4.3.0-4.6.99',
			'php' => '5.2.11-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:15:{s:6:"README";s:4:"d41d";s:34:"class.tx_socialpostd_scheduler.php";s:4:"1e30";s:16:"ext_autoload.php";s:4:"23c0";s:21:"ext_conf_template.txt";s:4:"20ec";s:12:"ext_icon.gif";s:4:"eea0";s:17:"ext_localconf.php";s:4:"a5ca";s:14:"ext_tables.php";s:4:"d5bb";s:14:"ext_tables.sql";s:4:"dd62";s:24:"ext_typoscript_setup.txt";s:4:"88be";s:16:"locallang_db.xlf";s:4:"6c7e";s:16:"locallang_db.xml";s:4:"f161";s:25:"lib/facebook/facebook.php";s:4:"1c4c";s:23:"lib/tinyurl/tinyurl.php";s:4:"37d6";s:21:"lib/twitter/OAuth.php";s:4:"d645";s:28:"lib/twitter/twitteroauth.php";s:4:"4fe4";}',
	'suggests' => array(
	),
);

?>