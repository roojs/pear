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
// | Authors: Wolfram Kriesing <wolfram@kriesing.de>                      |
// |          Naoki Shima <naoki@avantexchange.com>                       |
// +----------------------------------------------------------------------+
//
//  $Id: Common.php 110339 2003-01-04 11:55:29Z mj $
//

/**
*   this class provides language functionality, such as
*   determining the language of a given string, etc.
*   iso639-1 compliant, 2 letter code is used
*   iso639-1 http://www.loc.gov/standards/iso639-2/langcodes.html
*
*   @package  Language
*   @access   public
*   @author   Wolfram Kriesing <wolfram@kriesing.de>
*   @version  2001/12/29
*/
class I18N_Messages_Common
{

    // {{ properties

    /**
    * Holds messageID to corresponding message mapping
    *
    * @type  : array
    * @access: private
    */
    var $_message = array();

    // {{ constructor

    /**
    *
    *
    *   @access     public
    *   @author
    *   @version
    */
    function __construct( )
    {
# FIXXME pass a resource to the constructor which can be used to determine the
# language of a string, it should be possible to use XML, DB, or whatever
# this can then be used as a replacement for the array as used now
    }

    // }}
    // {{ I18N_Messages_Common()

    /**
    *   for pre-ZE2 compatibility
    *
    *   @access     public
    *   @author
    *   @version
    */
    function I18N_Messages_Common( )
    {
        return $this->__construct();
    }

    // }}
    // {{ determineLanuguage()

    /**
    *   trys to get the language of a given string
    *
    *   @access     public
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @version    01/12/29
    *   @param      string  $string     the string which is used to try and determine its language
    *   @return     string  iso-string for the language
    *
    */
    function determineLanguage( $string , $source='default' )
    {

        // include a file here, so one can provide its own file,
        // and to reduce compile time for php, since it will only be included when needed
        // the file that gets included might become very big
        if( $source=='default' )
            include('I18N/Messages/determineLanguage.inc.php');
        else
            include($source);  // include the file given as parameter, it only needs to provide an array, as in the above included file

        // replace all non word-characters by a space, i hope that is ok for all languages
        $string = preg_replace( '/[\W\s]/' , ' ' ,$string);

        $splitString = explode(' ',$string);        // get each single word in a field
        foreach( $splitString as $key=>$aString )   // remove spaces around the word and make it lower case
            $splitString[$key] = strtolower(trim($aString));

        // simply intersect each language array with the array that we created by splitting the string
        // and the result that's size is the biggest is our language
        foreach( $languages as $lang=>$aLanguage )
            $results[$lang] = sizeof(array_intersect($splitString,$aLanguage));

        arsort($results);
        reset ($results);
        list($lang,) = each($results);

        return $lang;

    }

    // }}
    // {{ get()

    /**
    * Look for and return the message corresponds to the messageID passed.
    * Returns messageID when the corresponding message is not found
    *
    * @return: string
    * @access: public
    * @author: Naoki Shima <naoki@avantexchange.com>
    */
    function get($messageID = "")
    {
        return ($messageID !== "" && is_array($this->_message) && in_array($messageID, array_keys($this->_message))) ? $this->_message[$messageID] :$messageID;
    }

    // }}
    // {{ _()

    /**
    * Alias for get(). Function name might not be appropriate because it conflicts PEAR coding standard
    * that this is meant to be public function
    *
    * @param : string        messageID
    * @return: string        corresponding message
    * @access: public
    * @author: Naoki Shima <naoki@avantexchange.com>
    */
    function _($messageID = "")
    {
        return $this->get($messageID);
    }

    // }}
    // {{ set()

    /**
    * Set message ID to corresponding string
    *
    * @return: boolean
    * @access: public
    * @author: Naoki Shima <naoki@avantexchange.com>
    */
    function set($messageID = "",$str = "")
    {
        if($messageID === "") {
            return false;
        }
        if($str === "" && is_array($messageID)) {
            // user is passing an array
            $this->_message = $messageID;
        } else {
            $this->_message[$messageID] = $str;
        }
        return true;
    }

    // }}
    // {{ setCharset()

    /**
    * Set charset of message
    *
    * @param : string        Charset
    *
    * @return: void
    * @access: public
    * @author: Naoki Shima <naoki@avantexchange.com>
    */
    function setCharset($charset  = I18N_MESSAGES_DEFAULT_CHARSET)
    {
        $this->_charset = $charset;
    }

    // }}
    // {{ getCharset()

    /**
    * Returns charset of message. Returns null if it's not set.
    *
    * @return: mixed         Charset
    * @access: public
    * @author: Naoki Shima <naoki@avantexchange.com>
    */
    function getCharset()
    {
        return ($this->_charset ? $this->_charset: false);
    }

    // }}
    // {{ bindDomain()

    /**
    * Bind domain to use
    * If domain is not passed and there's already a value set to domain,
    * then this method returns current domain.
    *
    * @param : string
    *
    * @return: string       Current domain
    * @access: public
    * @author: Naoki Shima
    */
    function bindDomain($domain = '')
    {
        if($domain === '') {
            return ($this->_domain ? $this->_domain : '');
        }
        $this->_domain = $domain;
        return $domain;
    }

    // }}
    // {{ bindLanguage()

    /**
    * Bind language to use
    * If language is not passed and there's already a value set to domain,
    * then this method returns current domain.
    *
    * @param : string
    *
    * @return: string       Current language
    * @access: public
    * @author: Naoki Shima <naoki@avantexchange.com>
    */
    function bindLanguage($lang = '')
    {
        if($lang === '') {
            return ($this->_lang ? $this->_lang : '');
        }
        $this->_lang = $lang;
        return $lang;
    }

    // }}
} // end of class
?>
