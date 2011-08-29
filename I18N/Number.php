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
// $Id: Number.php 145353 2003-12-01 23:05:56Z cain $

require_once 'I18N/Format.php';
                                     
/**
*   this is just a basic implementation for now, but one day
*   this
*       http://java.sun.com/docs/books/tutorial/i18n/format/numberpattern.html
*   should be implemented, anyone with a DecimalFormat-class please step forward :-)
*
*   @package    I18N
*
*/
class I18N_Number extends I18N_Format
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

    var $_currentFormat = I18N_NUMBER_FLOAT;
//    var $_currentPercentFormat = I18N_NUMBER_FLOAT;

    var $_customFormats = array();

    /**
    *   format a given number depending on the locale
    *
    *   @version    02/11/22
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      mixed   the number to be formatted
    *   @return     string  the formatted number
    */
    function format( $number , $format=null )
    {
        if( $format == null ){
            $format = $this->getFormat();
        }

        // handle custom formats too
        if ($format >= I18N_CUSTOM_FORMATS_OFFSET) {
            if (isset($this->_customFormats[$format])) {
                $numberFormat = $this->_customFormats[$format];
            }
        } else {
            $numberFormat = $this->_localeObj->numberFormat[$format];        
        }
        return call_user_func_array( 'number_format' , array_merge( array($number),$numberFormat) );
    }



    function formatPercent()
    {
    }

}
?>
