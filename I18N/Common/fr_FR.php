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
// $Id: fr_FR.php 110339 2003-01-04 11:55:29Z mj $
                                
require_once 'I18N/Common/fr.php';

class I18N_Common_fr_FR extends I18N_Common_fr
{


    /**
    *   the NUMBER stuff
    *   @var    array   the same parameters as they have to be passed to the number_format-funciton
    */
    var $numberFormat = array(
                                I18N_NUMBER_FLOAT   =>  array('3',',',' '),
                                I18N_NUMBER_INTEGER =>  array('0',',',' '),
                            );

    /**
    *   @var    array   the first is the currency symbol, second is the international currency symbol
    */
    var $currencyFormats =  array(
// FIXXME how do we handle the euro sign here, unicode is different than the HTML representation!!!
// this is the unicode for it ...
//                                    I18N_CURRENCY_LOCAL         =>  array( "% \u020A" ,    '2',',','.' ),
                                    I18N_CURRENCY_LOCAL         =>  array( "% &euro;" ,    '2',',','.' ),
                                    I18N_CURRENCY_INTERNATIONAL =>  array( '% Eur' ,  '2',',','.' ),
                                );


}
?>
