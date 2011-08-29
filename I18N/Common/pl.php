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
// | Authors: Piotr Klaban <makler@man.torun.pl>                          |
// |                                                                      |
// +----------------------------------------------------------------------+//
// $Id: pl.php 110339 2003-01-04 11:55:29Z mj $

class I18N_Common_pl
{
                        
    var $days = array( 'Niedziela' , 'Poniedzia³ek' , 'Wtorek' , '¦roda' , 'Czwartek' , 'Pi±tek' , 'Sobota' );

    var $daysAbbreviated = array( 'Ni','Po','Wt','¦r','Cz','Pi','So');

    var $monthsAbbreviated = array( 'sty' , 'lut' , 'mar' , 'kwi', 'maj' , 'cze' , 'lip' ,'sie' , 'wrz' , 'pa¼' , 'lis' , 'gru' );

    var $months = array(
                            'styczeñ',
                            'luty',
                            'marzec',
                            'kwiecieñ',
                            'maj',
                            'czerwiec',
                            'lipiec',
                            'sierpieñ',
                            'wrzesieñ',
                            'pa¼dziernik',
                            'listopad',
                            'grudzieñ'
                         );


    var $dateFormats = array(
                            I18N_DATETIME_SHORT     =>  'd.m.y',
                            I18N_DATETIME_DEFAULT   =>  'd.m.Y',
                            I18N_DATETIME_MEDIUM    =>  'd M Y',// ???? what shall medium look like????
                            I18N_DATETIME_LONG      =>  'd F Y',
                            I18N_DATETIME_FULL      =>  'l, d F Y'
                        );
    var $timeFormats = array(
                            I18N_DATETIME_SHORT     =>  'H:i',
                            I18N_DATETIME_DEFAULT   =>  'H:i:s',
                            I18N_DATETIME_MEDIUM    =>  'H:i:s', // ???? what shall medium look like????
                            I18N_DATETIME_LONG      =>  'H:i:s T O',
                            I18N_DATETIME_FULL      =>  '\g\o\d\z\. H:i T O'
                        );      
                        
    /**
    *   the NUMBER stuff
    *   @var    array   the same parameters as they have to be passed to the number_format-funciton
    */
    var $numberFormat = array(
                                I18N_NUMBER_FLOAT   =>  array('3',',','.'),
                                I18N_NUMBER_INTEGER =>  array('0',',','.'),
                            );

    /**
    *   @var    array   the first is the currency symbol, second is the international currency symbol
    */
    var $currencyFormats =  array(
                                // probably about year 2006 there will be Euro currency
                                    I18N_CURRENCY_LOCAL         =>  array( '% z³' ,    '2',',','.' ),
                                    I18N_CURRENCY_INTERNATIONAL =>  array( '% PLN' ,   '2',',','.' ),
                                );

}
?>
