<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997, 1998, 1999, 2000, 2001, 2002, 2003 The PHP Group       |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Wolfram Kriesing <wk@visionp.de>                            |
// |                                                                      |
// +----------------------------------------------------------------------+//
// $Id: Format.php 134347 2003-07-07 12:50:43Z cain $

require_once 'PEAR.php';

// define those here, since the dateTime and currency 
// use those constants too
define( 'I18N_NUMBER_FLOAT' ,           1 );
define( 'I18N_NUMBER_INTEGER' ,         2 );


define( 'I18N_CURRENCY_LOCAL',          1 );
define( 'I18N_CURRENCY_INTERNATIONAL',  2 );

                      
// this is the offset for the custom index
define( 'I18N_CUSTOM_FORMATS_OFFSET',   100);


/**
*
*   @package    I18N
*
*/
class I18N_Format extends PEAR
{
     
    /**
    *   this var contains the current locale this instace works with
    *
    *   @access     protected
    *   @var        string  this is a string like 'de_DE' or 'en_US', etc.
    */
    var $_locale;

    /**
    *   the locale object which contains all the formatting specs
    *
    *   @access     protected
    *   @var        object
    */
    var $_localeObj = null;
         
                                  
    /**          
    *   do override this value !!!!
    *   @abstract
    */
    var $_currentFormat = null;

    var $_customFormats = array();
    
    /**
    *
    *
    *   @version    02/11/22
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @return     void
    *   @access     public
    */
    function I18N_Format($locale)
// FIXXME if no locale is given try to use the local on of the system
// if it cant be detected use english and the user probable will use setFormat anyway
    {
        parent::PEAR();
        $this->_locale = $locale;

//FIXXME catch locales that we dont have a class for yet, and use default class, which
// translates using google or something ...
        if( include_once "I18N/Common/$locale.php" ){
            $class = "I18N_Common_$locale";
            $this->_localeObj = new $class();
        }
    }
         
    /**
    *   This method creates a singleton instance for the given class.
    *   This is here, just so the DateTime, Number and Currency dont have
    *   to implement the same thing again, they only need to have a public method
    *   singleton() which calls this method here.
    *
    *   @see    singleton()
    *   @static 
    *   @access private
    *   @param  string  the locale to use, i.e. 'de_DE'
    *   @param  string  the name of the class to return an object for
    *   @return object  an instance of this class
    */
    function &_singleton($locale,$class)
    {
        static $obj;
        if (!isset($obj[$class])) {
            $obj[$class] = new $class($locale);
        }
        return $obj[$class];
    }
        
    /**
    *   define a custom format given by $format and return the $format-id
    *   the format-id can be used to call format( x , format-id ) to
    *   tell the method you want to use the format with that id
    *
    *   @see        format()
    *   @version    02/11/20
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      string  defines a custom format
    *   @return     int     the format-id, to be used with the format-method
    */
    function setFormat($format)
    {
        return $this->_setFormat($format);
    }

    function _setFormat($format,$what='')
    {
        if((int)$format===$format) {         // shall we only set the format to an already defined format?
            return $this->{'_current'.ucfirst($what).'Format'} = $format;
        }          

        // save a custom format and return the id of it, so the user can switch to it
        // whenever needed
        $index = (int)I18N_CUSTOM_FORMATS_OFFSET + sizeof($this->_customFormats);
        $this->_customFormats[$index] = $format;
        $this->{'_current'.ucfirst($what).'Format'} = $index;
                             
        return $index;
    }

    /**
    *
    */
    function getFormat()
    {
        return $this->_currentFormat;
    }



}
?>
