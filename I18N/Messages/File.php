<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Naoki Shima <naoki@avantexchange.com>                       |
// |                                                                      |
// +----------------------------------------------------------------------+
//
//  $Id:
//
require_once 'Common.php';

/**
* Get message, and charset from php file, and return corresponding message
* when get() is called.
*
* Full path to the file may look like this:
*   /usr/local/apache/htdocs/lang/ja/general.php
*   In this case, you can use I18N_Messages_File as follows:
*     your script:
*       $domain = 'general';
*       $lang   = 'ja';
*       $dir    = '/usr/local/apache/htdocs/lang/';
*       require_once 'I18N/Messages/File.php';
*       $i18n =& I18N_Messages_File($lang,$domain,$dir);
*       $i18n->_('hello');
*
*     /usr/local/apache/htdocs/lang/ja/general.php:
*       $this->setCharset('euc-jp'); // Set charset of this file
*       $messages = array('hello' => 'Kon nichiwa'); // you can put more than one message in this.
*       $this->set($messages); // or $this->set('Hello', 'Kon nichiwa');
*/
class I18N_Messages_File extends I18N_Messages_Common
{

    // {{ properties

    /**
    * Holds directory information
    *
    * @type  : string        Directory name
    * @access: private
    */
    var $_dir;

    // }}
    // {{ constructor

    /**
    * Save Lanuguage and the directory name where language file resides.
    * Then load the file.
    *
    * @param : string          Lanuguage Code
    * @param : string          Directory Name
    *
    * @return: void
    * @access: public
    */
    function __construct($lang = 'en', $domain = '',$dir = './')
    {
        parent::__construct();
        $this->setDir($dir);
        $this->bindLanguage($lang);
        $this->bindDomain($domain);
        $this->_load();
    }

    // }}
    // {{ I18N_Messages_File()

    /**
    * For pre-Zend2 compatibility. Call actual constructor
    *
    * @param : string          Lanuguage Code
    * @param : string          Directory Name
    *
    * @return: void
    * @access: public
    */
    function I18N_Messages_File($lang = 'en', $domain = '', $dir = './')
    {
        $this->__construct($lang,$domain,$dir);
    }
    
    /**
    * Load the lanuguage file
    *
    * @param : string      Language code
    *
    * @return: void
    * @access: private
    */
    function _load()
    {
        include_once $this->getDir().$this->bindLanguage().'/'.$this->bindDomain().'.php';
    }

    /**
    * Set directory
    *
    * @return: string     Directory name
    * @access: public
    */
    function setDir($dir)
    {
        $this->_dir = $dir;
    }

    /**
    * Return directory name
    *
    * @return: string     Directory name
    * @access: public
    */
    function getDir()
    {
        return $this->_dir;
    }

    function get($messageID)
    {
        // make sure it's loaded. for just after bindDomain() or bindLanuguage() method is called.
        $this->_load();
        return ($messageID !== "" && is_array($this->_message) && in_array($messageID, array_keys($this->_message))) ? $this->_message[$messageID] :$messageID;
    }
}
?>