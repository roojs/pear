<?php
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997, 1998, 1999, 2000, 2001, 2002 The PHP Group       |
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
// $Id: fr.php 119834 2003-03-11 02:56:48Z pajoye $

class I18N_Common_fr
{
    var $days = array( 'dimanche' , 'lundi' , 'mardi' , 'mercredi' , 'jeudi' , 'vendredi' , 'samedi' );

    var $daysAbbreviated = array( 'dim' ,'lun','mar','mer','jeu','ven','sam');

    var $monthsAbbreviated = array( 'jan' , 'fév' , 'mar' , 'avr' , 'mai' , 'jun' ,'jul' , 'aug' , 'sep' , 'oct' , 'nov' , 'déc' );

    var $months = array(
                            'janvier',
                            'février',
                            'mars',
                            'avril',
                            'mai',
                            'juin',
                            'juillet',
                            'août',
                            'septembre',
                            'octobre',
                            'novembre',
                            'décembre'
                         );

    var $dateFormats = array(
                            I18N_DATETIME_SHORT     =>  'd/m/y',
                            I18N_DATETIME_DEFAULT   =>  'd-M-Y',
                            I18N_DATETIME_MEDIUM    =>  'd-M-Y',// ???? what shall medium look like????
                            I18N_DATETIME_LONG      =>  'd F Y',
                            I18N_DATETIME_FULL      =>  'l, d \d\e F \d\e Y'
                        );
    var $timeFormats = array(
                            I18N_DATETIME_SHORT     =>  'H:i',
                            I18N_DATETIME_DEFAULT   =>  'H:i:s',
                            I18N_DATETIME_MEDIUM    =>  'H:i:s', // ???? what shall medium look like????
                            I18N_DATETIME_LONG      =>  'H:i:s T O',
                            I18N_DATETIME_FULL      =>  'H:i T O'
                        );


    /**
    *   the NUMBER stuff
    *   @var    array   the same parameters as they have to be passed to the number_format-funciton
    */
    var $numberFormat = array(
                                I18N_NUMBER_FLOAT   =>  array('3',',',' '),
                                I18N_NUMBER_INTEGER =>  array('0',',',' '),
                            );

}
?>