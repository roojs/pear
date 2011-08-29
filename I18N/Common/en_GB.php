<?php
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
// $Id: en_GB.php 122844 2003-04-08 10:32:09Z cain $

require_once('I18N/Common/en.php');

class I18N_Common_en_GB extends I18N_Common_en
{        

// TO BE DONE
    /**
    *   @var    array   the first is the currency symbol, second is the international currency symbol
    */
    var $currencyFormats =  array(
                                    I18N_CURRENCY_LOCAL         =>  array( "???%" ,    '2' , '.' , ',' ),
                                    I18N_CURRENCY_INTERNATIONAL =>  array( '??? %' , '2' , '.' , ',' ),
                                );



}

?>
