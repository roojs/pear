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
// $Id: Currency.php 113734 2003-01-28 11:19:35Z cain $

require_once 'I18N/Number.php';

/**
*
*   @package    I18N
*
*/
class I18N_Currency extends I18N_Number
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


    function format( $amount , $format=I18N_CURRENCY_LOCAL )
    {
        if( $format == null ){
            $format = $this->getFormat();
        }
                                         
        // normally this is used
        $numberFormat = $this->_localeObj->currencyFormats[$format];
        // handle custom formats too
        if( $format >= I18N_CUSTOM_FORMATS_OFFSET )
        {
            if( isset($this->_customFormats[$format]) )
            {
                $numberFormat = $this->_customFormats[$format];
            }
        }
        // remove the currency symbol, which is the first value
        $currencyFormat = array_shift($numberFormat);

        $formattedAmount = call_user_func_array( 'number_format' , array_merge( array($amount),$numberFormat) );

        return str_replace( '%' , $formattedAmount , $currencyFormat );
    }

}
?>
