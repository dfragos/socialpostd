<?php

/***************************************************************
    *  Copyright notice
    *
    *  (c) 2011 Dionysios Fragkopoulos <me@dfragos.me>
    *  All rights reserved
    *
    *  This script is part of the TYPO3 project. The TYPO3 project is
    *  free software; you can redistribute it and/or modify
    *  it under the terms of the GNU General Public License as published by
    *  the Free Software Foundation; either version 2 of the License, or
    *  (at your option) any later version.
    *
    *  The GNU General Public License can be found at
    *  http://www.gnu.org/copyleft/gpl.html.
    *
    *  This script is distributed in the hope that it will be useful,
    *  but WITHOUT ANY WARRANTY; without even the implied warranty of
    *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    *  GNU General Public License for more details.
    *
    *  This copyright notice MUST APPEAR in all copies of the script!
    ***************************************************************/

require_once(t3lib_extMgm::extPath('socialpostd', 'lib/facebook/facebook.php'));
require_once(t3lib_extMgm::extPath('socialpostd', 'lib/twitter/twitteroauth.php'));
require_once(t3lib_extMgm::extPath('socialpostd', 'lib/tinyurl/tinyurl.php'));

require_once(PATH_tslib.'class.tslib_fe.php');
require_once(PATH_t3lib.'class.t3lib_userauth.php');
require_once(PATH_tslib.'class.tslib_feuserauth.php');
require_once(PATH_t3lib.'class.t3lib_cs.php');
require_once(PATH_tslib.'class.tslib_content.php');
require_once(PATH_t3lib.'class.t3lib_tstemplate.php');
require_once(PATH_t3lib.'class.t3lib_page.php');
require_once(PATH_t3lib.'class.t3lib_timetrack.php');
require_once(PATH_t3lib."class.t3lib_extobjbase.php");
require_once(PATH_t3lib."class.t3lib_tsparser_ext.php");

class tx_socialpostd_scheduler extends tx_scheduler_Task {

    var $cobj;
    var $extKey = "socialpostd";        // The extension key.
        
    var $confEX;      // TypoScript Configuration for this extension
    
    /**
     * Function executed from the Scheduler.
     *
     * @return    void
     */
    public function execute() {

        global $GLOBALS, $TYPO3_CONF_VARS;

        $confEX = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['socialpostd']);

        $this->cObj = t3lib_div::makeInstance('tslib_cObj');

        $GLOBALS['TSFE'] = t3lib_div::makeInstance('tslib_fe', $TYPO3_CONF_VARS, t3lib_div::_GP('id'),    t3lib_div::_GP('type'),    t3lib_div::_GP('no_cache'), t3lib_div::_GP('cHash'), t3lib_div::_GP('jumpurl'),    t3lib_div::_GP('MP'), t3lib_div::_GP('RDCT'));
        
        $GLOBALS['TT'] = t3lib_div::makeInstance('t3lib_timeTrack');

        $GLOBALS['TT']->start();

        $GLOBALS['TSFE']->connectToDB();
        $GLOBALS['TSFE']->initFEuser();
        $GLOBALS['TSFE']->fetch_the_id();
        $GLOBALS['TSFE']->getPageAndRootline();
        $GLOBALS['TSFE']->initTemplate();
        $GLOBALS['TSFE']->forceTemplateParsing = 1;
        $GLOBALS['TSFE']->getConfigArray();
        $GLOBALS['TSFE']->initUserGroups();
        $GLOBALS['TSFE']->determineId();

        $extglobalsettings = array (
            'webUrl' => $confEX['plugin.']['tx_socialpostd.']['webUrl'],
            'newsstorage' => $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_socialpostd.']['config.']['tt_news.']['newsstorage'],
            'singleview' => $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_socialpostd.']['config.']['tt_news.']['newssingleview'],
        );

        if ($confEX['plugin.']['tx_socialpostd.']['config.']['fbenabled']) {
            $fbvars = array(
                'appId'  => $confEX['plugin.']['tx_socialpostd.']['facebook.']['appId'],
                'secret' => $confEX['plugin.']['tx_socialpostd.']['facebook.']['secret'],
                'authCode' => $confEX['plugin.']['tx_socialpostd.']['facebook.']['authCode'],
                'pageId' => $confEX['plugin.']['tx_socialpostd.']['facebook.']['pageId'],
                'groupId' => $confEX['plugin.']['tx_socialpostd.']['facebook.']['groupId'],
            );

            $this->cObj->start(array(),'');

            Facebook::$CURL_OPTS[CURLOPT_SSL_VERIFYPEER] = false;

            $facebook = new Facebook(
                array(
                      'appId'  => $fbvars['appId'],
                      'secret' => $fbvars['secret'],
                      'cookie' => true,
                )
            );

            $url = 'https://graph.facebook.com/oauth/access_token?client_id='.$fbvars['appId'].'&client_secret=' .$fbvars['secret'].'&code=' .$fbvars['authCode'] .'&redirect_uri=' .$extglobalsettings['webUrl'];
            
            if ($accessToken = file_get_contents($url)) {
                $accessToken = substr($accessToken, 13);
                                
                $session = array(
                    'uid' => '',
                    'session_key' => '',
                    'secret' => '',
                    'access_token' => $accessToken,
                    'expires' => 0
                );
                ksort($session);
                $sessionStr = '';
                foreach($session as $sessionKey => $sessionValue) $sessionStr .= implode("=", array($sessionKey, $sessionValue));
                $session["sig"] = md5($sessionStr . $fbvars['secret']);
                $facebook->setSession($session, false);

                $session = $facebook->getSession();
                
                $select = 'tt_news_cat.image AS cat_image, tt_news.uid AS news_uid, tt_news.datetime AS news_datetime, tt_news.ext_url AS news_ext_url, tt_news.pid AS news_storage, tt_news.type AS news_type, tt_news.title AS news_title, tt_news.short AS news_short, tt_news.bodytext AS news_bodytext, tt_news.image AS news_image';
                $localtable = 'tt_news';
                $mmtable = 'tt_news_cat_mm';
                $ftable = 'tt_news_cat';
                $where = ' AND tt_news.uid IS NOT NULL'.$this->enableFields('tt_news_cat').' AND tt_news.tx_socialpostd_fb_publish = 0  AND tt_news.tx_socialpostd_fb_ignor_publish = 0  AND tt_news.pid='. $extglobalsettings['newsstorage'] . ' ' .$this->enableFields('tt_news');
                $res_fb = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query($select, $localtable, $mmtable, $ftable, $where);     
                
                $newsUid = '';
                
                $newsImagePath = $GLOBALS['TCA']['tt_news']['columns']['image']['config']['uploadfolder'];
                if(!$newsImagePath) $newsImagePath = 'uploads/pics';
                                
                while ($row_fb = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_fb)) {
                                        
                    $this->getHrDateSingle($row_fb['news_datetime']);
                    
                    try{
                        $typolinkconf = array(
                            'parameter' => $extglobalsettings['singleview'],
                            'additionalParams' => '&tx_ttnews[tt_news]=' .$row_fb['news_uid'].'&tx_ttnews[year]='.$this->piVars['year'].'&tx_ttnews[month]='.$this->piVars['month'].'&tx_ttnews[day]='.$this->piVars['day'],
                            'useCacheHash' => true,
                            'returnLast' => 'url',
                        );
                                                
                        if ($row_fb['news_type'] == 0) {
                            $newslink =  $extglobalsettings['webUrl'] . 'index.php?id='.$extglobalsettings['singleview'].'&tx_ttnews[tt_news]=' .$row_fb['news_uid'].'&tx_ttnews[year]='.$this->piVars['year'].'&tx_ttnews[month]='.$this->piVars['month'].'&tx_ttnews[day]='.$this->piVars['day'];
                        } elseif ($row_fb['news_type'] == 2) {
                            $newslink = $row_fb['news_ext_url'];
                        }
                                                
                        if ($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_socialpostd.']['config.']['tt_news.']['catimage']) {
                            $image = $row_fb['cat_image'];
                        } else {
                            if (!$image = $row_fb['news_image']) {
                                $image = $row_fb['cat_image'];
                            }
                        }
                        
                        if($image){
                            if(strpos($image, ',') > 0) {
                                //several Pictures in News, only the first will be taken
                                $imagePath = $extglobalsettings['webUrl'] . $newsImagePath .'/' .substr($image, 0, strpos($image, ','));
                            } else {
                                //only one Picture in News, this will be taken
                                $imagePath = $extglobalsettings['webUrl'] . $newsImagePath .'/' .$image;
                            }
                        }
                        
                        if (!$fbdescription = $row_fb['news_short']) {
                            if (!$fbdescription = $row_fb['news_bodytext']) {
                                $fbdescription = ' ';
                            }
                        }
                        
                        $attachment = array(
                            'access_token' => $accessToken,
                            'link' => $newslink,
                            'description' => $fbdescription,
                            'name' => $row_fb['news_title'],
                            'picture' => $imagePath,
                        );

                        if($fbvars['pageId']) {
                            if (!($fb_reply = $facebook->api('/' .$fbvars['pageId'] .'/feed', 'post', $attachment))) {
                                $GLOBALS['BE_USER']->simplelog('fan-page: '.$fb_reply->error, $this->extKey, 1);
                                return false;
                            }
                        }
                        if($fbvars['groupId']) {
                            if (!($fb_reply = $facebook->api('/' .$fbvars['groupId'] .'/feed', 'post', $attachment))) {
                                $GLOBALS['BE_USER']->simplelog('group-page: '.$fb_reply->error, $this->extKey, 1);
                                return false;
                            }
                        }

                        $newsUid .= $row_fb['news_uid'] .',';
                        
                    } catch(Exception $e) {
                        $GLOBALS['BE_USER']->simplelog($e, $this->extKey, 1);
                        return false;
                    }
                }

                if ($GLOBALS['TYPO3_DB']->sql_num_rows($res_fb)) {
                    $newsUid = substr($newsUid, 0, -1);
                    $updateArray = array(
                        'tx_socialpostd_fb_publish' => '1'
                    );
                    $where = 'uid in (' .$newsUid .')';
                    $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_news', $where, $updateArray );
                                
                    $GLOBALS['BE_USER']->simplelog($GLOBALS['TYPO3_DB']->sql_num_rows($res_fb).' news record(s) published on Facebook', $this->extKey, 0);
                }
                
                $GLOBALS['TYPO3_DB']->sql_free_result($res_fb);
            } else {
                $GLOBALS['BE_USER']->simplelog('Problem Connecting to Facebook', $this->extKey, 1);
                return false;
            }
            
            
        }

        $newsUid = '';
        
        if ($confEX['plugin.']['tx_socialpostd.']['config.']['twenabled']) {
            $twittervars = array(
                'User'  => $confEX['plugin.']['tx_socialpostd.']['twitter.']['User'],
                'Pass' => $confEX['plugin.']['tx_socialpostd.']['twitter.']['Pass'],
                'consumerkey' => $confEX['plugin.']['tx_socialpostd.']['twitter.']['consumerkey'],
                'consumersecret' => $confEX['plugin.']['tx_socialpostd.']['twitter.']['consumersecret'],
                'accesstoken' => $confEX['plugin.']['tx_socialpostd.']['twitter.']['accesstoken'],
                'accesstokensecret' => $confEX['plugin.']['tx_socialpostd.']['twitter.']['accesstokensecret'],
                'postfield' => $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_socialpostd.']['config.']['tt_news.']['twitter.']['postfield'],
                'linkback' => $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_socialpostd.']['config.']['tt_news.']['twitter.']['linkback']
            );

            $table = 'tt_news';
            $where = 'tx_socialpostd_tw_publish = 0  AND tx_socialpostd_tw_ignor_publish = 0 AND pid='. $extglobalsettings['newsstorage'] . ' ' .$this->enableFields('tt_news');
            $res_tw = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, $where);
            
            $newsUid = '';

            while ($row_tw = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_tw)) {
                try{
                    $typolink_conf = array(
                        'no_cache' => false,
                        'parameter' => $extglobalsettings['singleview'],
                        'additionalParams' => '&tx_ttnews[tt_news]=' .$row_tw['uid'].'&tx_ttnews[year]='.$this->piVars['year'].'&tx_ttnews[month]='.$this->piVars['month'].'&tx_ttnews[day]='.$this->piVars['day'],
                        'useCacheHash' => true,
                    );
                    if ($row_tw['type'] == 0) {
                        $newslink =  $extglobalsettings['webUrl'] . 'index.php?id='.$extglobalsettings['singleview'].'&tx_ttnews[tt_news]=' .$row_tw['uid'].'&tx_ttnews[year]='.$this->piVars['year'].'&tx_ttnews[month]='.$this->piVars['month'].'&tx_ttnews[day]='.$this->piVars['day'];
                    } elseif ($row_tw['type'] == 2) {
                        $newslink = $row_tw['ext_url'];
                    }
                                                            
                    $singleUrl = '';
                    if ($twittervars['linkback']) {
                        $singleUrl = ' '.$this->createShortUrl($newslink);
                    }
                                                                           
                    if (!($twit_reply = $this->twit(($this->twittermessageclean($row_tw[$twittervars['postfield']], $singleUrl)),$twittervars['consumerkey'],$twittervars['consumersecret'],$twittervars['accesstoken'],$twittervars['accesstokensecret']))) {
                        $newsUid .= $row_tw['uid'] .',';
                    } elseif ($twit_reply == 'Status is a duplicate.') {
                        $newsUid .= $row_tw['uid'] .',';
                        $GLOBALS['BE_USER']->simplelog($row_tw[$twittervars['postfield']].' was a duplicate.', $this->extKey, 1);
                    } else {
                        $newsUid = substr($newsUid, 0, -1);
                        $updateArray = array(
                            'tx_socialpostd_tw_publish' => '1'
                        );
                        $where = 'uid in (' .$newsUid .')';
                        $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_news', $where, $updateArray );

                        $GLOBALS['BE_USER']->simplelog($twit_reply, $this->extKey, 1);
                        return false;
                    }
                } catch(Exception $e) {
                    $GLOBALS['BE_USER']->simplelog($e, $this->extKey, 1);
                    return false;
                }
            }

            if ($GLOBALS['TYPO3_DB']->sql_num_rows($res_tw)) {
                $newsUid = substr($newsUid, 0, -1);
                $updateArray = array(
                    'tx_socialpostd_tw_publish' => '1'
                );
                $where = 'uid in (' .$newsUid .')';
                $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_news', $where, $updateArray );

                $GLOBALS['BE_USER']->simplelog($GLOBALS['TYPO3_DB']->sql_num_rows($res_tw).' news record(s) published on Twitter', $this->extKey, 0);
            }
            
            $GLOBALS['TYPO3_DB']->sql_free_result($res_tw);
        }
        
        return true;
    }
    
    /**
     * Clean up the message post to Twitter
     *
     * @param    string        $message
     * @param    string        $link
     * @return    string        Final message to be post
     */
    function twittermessageclean($message, $link) {
        $message = htmlspecialchars_decode(strip_tags($message),ENT_QUOTES);
        $message = str_replace(array('<','>','&'), array(' ',' ',' and '), $message);
        $message = $this->fixEncoding($message);
        $message = (strlen($message)+strlen($link) > 137) ? mb_substr($message, 0, 137-strlen($link), 'UTF-8').'...': $message;
        $message = $message.$link;
        return $message;
    }

    /**
     * Implements enableFields call that can be used from regular FE and eID
     *
     * @param    string        $tableName    Table name
     * @return    string        SQL
     */
    function enableFields($tableName) {
        if ($GLOBALS['TSFE']) return $this->cObj->enableFields($tableName);
        $sys_page = t3lib_div::makeInstance('t3lib_pageSelect');
        return $sys_page->enableFields($tableName);
    }

    /**
     * converts the datetime of a record into variables you can use in realurl
     *
     * @param    integer        the timestamp to convert into a HR date
     * @return    void
     */
    function getHrDateSingle($tstamp) {
        $this->piVars['year'] = date('Y',$tstamp);
        $this->piVars['month'] = date('m',$tstamp);
        if (!$this->conf['useHRDatesSingleWithoutDay']) $this->piVars['day'] = date('d',$tstamp);
    }

    /**
     * Post data on Twitter using the API.
     *
     * @param    string        $twitter_data: Data to post on twitter.
     * @param     string           $twitterconsumerkey: Consumer Key
     * @param     string           $twitterconsumersecret: Consumer Secret
     * @param     string           $twitteraccesstoken: Assess Token
     * @param     string           $twitteraccesstokensecret: Assess Token Secret
     * @return   status
     */
    function twit($twitter_data,$twitterconsumerkey,$twitterconsumersecret,$twitteraccesstoken,$twitteraccesstokensecret) {
        $twitter = new TwitterOAuth(
            $twitterconsumerkey,
            $twitterconsumersecret,
            $twitteraccesstoken,
            $twitteraccesstokensecret
        );

        $reply= $twitter->post(
            'statuses/update',
            array('status' => $twitter_data)
        );
                
        return $reply->error;
    }

    /**
     * Shorten long url by converting it using tinyurl.com
     *
     * @param    string        $longURL: Long URL.
     * @return    string        Short version of long URL.
     */
    function createShortUrl($longURL) {
        // tinyurl.com
        $tiny = new TinyUrl();
        $shortURL = $tiny->create($longURL);
        return $shortURL;
    }
    
    /**
     * Check if string is in UTF-8 format and fix the encoding
     *
     * @param    array    string to check
     * @return    string   the UTF-8 encoded string
     */
    function fixEncoding($in_str) {
      if(mb_detect_encoding($in_str, 'UTF-8' , true))
        return $in_str;
      else
        return utf8_encode($in_str);
    }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/socialpost/class.tx_socialpostd_publish.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/socialpost/class.tx_socialpostd_publish.php']);
}

?>