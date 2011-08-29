<?php
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Wolfram Kriesing <wk@visionp.de>                            |
// |          Kovács Gergely <kgergely@dkgeast.hu>                        |
// +----------------------------------------------------------------------+
// $Id: hu.php 128187 2003-05-21 10:45:16Z cain $

class I18N_Common_hu
{

    var $days = array( 'vasárnap', 'hétfõ', 'kedd', 'szerda', 'csütörtök', 'péntek', 'szombat');
    
    var $daysAbbreviated = array( 'V', 'H', 'K', 'Sze', 'Cs', 'P', 'Szo' );

    var $monthsAbbreviated = array( 'jan', 'feb', 'márc', 'ápr', 'máj','jún', 'júl', 'aug', 'szept', 'okt', 'nov', 'dec' );

    var $months = array(
                            'január',
                            'február',
                            'március',
                            'április',
                            'május',
                            'június',
                            'július',
                            'augusztus',
                            'szeptember',
                            'október',
                            'november',
                            'december'
                        );

    var $dateFormats = array(
                            I18N_DATETIME_SHORT     =>  'Y-m-d',
                            I18N_DATETIME_DEFAULT   =>  'Y. M. d.',
                            I18N_DATETIME_MEDIUM    =>  'Y. M. d.',
                            I18N_DATETIME_LONG      =>  'Y. F d.',
                            I18N_DATETIME_FULL      =>  'Y. F d., l'
                        );
    var $timeFormats = array(
                            I18N_DATETIME_SHORT     =>  'H:i',
                            I18N_DATETIME_DEFAULT   =>  'H:i:s',
                            I18N_DATETIME_MEDIUM    =>  'H:i:s',
                            I18N_DATETIME_LONG      =>  'H:i:s T O',
                            I18N_DATETIME_FULL      =>  '\i\d\õ: H:i T O'
                        );

    /**
    * the NUMBER stuff
    * @var    array   the same parameters as they have to be passed to the number_format-funciton
    */
    var $numberFormat = array(
                                I18N_NUMBER_FLOAT   =>  array('3',',','.'),
                                I18N_NUMBER_INTEGER =>  array('0',',','.'),
                            );

    var $currencyFormats = array(
                                I18N_CURRENCY_LOCAL         =>  array("% Ft" , '2', ',', '.'),
                                I18N_CURRENCY_INTERNATIONAL =>  array('HUF %', '2', '.', ',')
                            );

}
?>
