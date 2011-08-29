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
// |          Jacques Marneweck <jacques@php.net>                         |
// +----------------------------------------------------------------------+//
// $Id: af.php 120053 2003-03-13 08:37:32Z cain $

class I18N_Common_af
{
    var $days = array( 'Sonday' , 'Maandag' , 'Dinsdag' , 'Woensdag' , 'Donderdag' , 'Vrydag' , 'Saterdag' );

    var $daysAbbreviated = array( 'Son','Maa','Din','Woe','Don','Vry','Sat');

    var $monthsAbbreviated = array( 'Jan' , 'Feb' , 'Mrt' , 'Apr' , 'Mei' , 'Jun' ,'Jul' , 'Aug' , 'Sep' , 'Okt' , 'Nov' , 'Des' );

    var $months = array(
                            'Januarie',
                            'Februarie',
                            'Maart',
                            'April',
                            'Mei',
                            'Junie',
                            'Julie',
                            'Augustus',
                            'September',
                            'Oktober',
                            'November',
                            'Desember'
                         );

    var $dateFormats = array(
                            I18N_DATETIME_SHORT     =>  'd/m/y',
                            I18N_DATETIME_DEFAULT   =>  'd-M-Y',
                            I18N_DATETIME_MEDIUM    =>  'd-M-Y',// ???? what shall medium look like????
                            I18N_DATETIME_LONG      =>  'd F Y',
                            I18N_DATETIME_FULL      =>  'l, d F Y'
                        );
    var $timeFormats = array(
                            I18N_DATETIME_SHORT     =>  'H:i',
                            I18N_DATETIME_DEFAULT   =>  'H:i:s',
                            I18N_DATETIME_MEDIUM    =>  'H:i:s', // ???? what shall medium look like????
                            I18N_DATETIME_LONG      =>  'H:i:s T O',
                            I18N_DATETIME_FULL      =>  'H:i \o\'\c\l\o\c\k T O'
                        );      
                        
    /**
    *   the NUMBER stuff
    *   @var    array   the same parameters as they have to be passed to the number_format-funciton
    */
    var $numberFormat = array(
                                I18N_NUMBER_FLOAT   =>  array('3','.',','),
                                I18N_NUMBER_INTEGER =>  array('0','.',','),
                            );

}
?>
