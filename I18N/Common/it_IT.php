<?php
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997, 1998, 1999, 2000, 2001, 2002, 2003 The PHP Group |
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
// |          Lorenzo Alberton <l.alberton@quipo.it>                      |
// +----------------------------------------------------------------------+//
// $Id: it_IT.php 110935 2003-01-07 17:42:57Z cain $

require_once('I18N/Common/it.php');

class I18N_Common_it_IT extends I18N_Common_it
{
    /**
    *   @var    array   the first is the currency symbol, second is the international currency symbol
    */
    var $currencyFormats =  array(
//                                  I18N_CURRENCY_LOCAL         =>  array("% \u020A", '2',',','.'),                                    I18N_CURRENCY_LOCAL         =>  array( '% &euro;' , '2' , ',', '.'),
                                    I18N_CURRENCY_LOCAL         =>  array('% &euro;',   '2',',','.'),
                                    I18N_CURRENCY_INTERNATIONAL =>  array('% Eur'   ,   '2',',','.')
                                );

}

?>
