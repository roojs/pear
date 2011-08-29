<?php
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Tommy Ipsen <tommy.ipsen@nalnet.dk>                         |
// +----------------------------------------------------------------------+
// $Id: da_DK.php 136856 2003-08-07 07:45:50Z cain $

class I18N_Common_da_DK
{

    var $days = array('søndag', 'mandag', 'tirsdag', 'onsdag', 'torsdag', 'fredag', 'lørdag');

    var $daysAbbreviated = array('søn', 'man', 'tir','ons','tor','fre','lør');

    var $monthsAbbreviated = array( 'Jan' , 'Feb' , 'Mar' , 'Apr' , 'Maj' , 'Jun' ,'Jul' , 'Aug' , 'Sep' , 'Okt' , 'Nov' , 'Dec' );

    var $months = array(
                            'januar',
                            'februar',
                            'marts',
                            'april',
                            'maj',
                            'juni',
                            'juli',
                            'august',
                            'september',
                            'oktober',
                            'november',
                            'december'
                        );

    var $dateFormats = array(
                            I18N_DATETIME_SHORT    =>  'j/n-y',
                            I18N_DATETIME_DEFAULT  =>  'd-M-Y',
                            I18N_DATETIME_MEDIUM    =>  'd-M-Y',
                            I18N_DATETIME_LONG      =>  'j. F Y',
                            I18N_DATETIME_FULL      =>  'l, \d. j. F Y'
                        );

    var $timeFormats = array(
                            I18N_DATETIME_SHORT     =>  'H:i',
                            I18N_DATETIME_DEFAULT   =>  'H:i:s',
                            I18N_DATETIME_MEDIUM    =>  'H:i:s',
                            I18N_DATETIME_LONG      =>  'H:i:s T O',
                            I18N_DATETIME_FULL      =>  '\k\l. H:i'
                        );      
                        
   /**
    * the NUMBER stuff
    * @var    array   the same parameters as they have to be passed to the number_format-funciton
    */
    var $numberFormat = array(
                                I18N_NUMBER_FLOAT   =>  array('3',',','.'),
                                I18N_NUMBER_INTEGER =>  array('0',',','.'),
                            );

}
?>
