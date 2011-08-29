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
// |          Bruno Pedro <bpedro@co.sapo.pt>                             |
// +----------------------------------------------------------------------+
// $Id: pt.php 128185 2003-05-21 10:35:10Z cain $

class I18N_Common_pt
{
    var $days = array('Domingo', 'Segunda-feira', 'Ter�a-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'S�bado');

    var $daysAbbreviated = array('Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'S�b');

    var $monthsAbbreviated = array('Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez');

    var $months = array(
                            'Janeiro',
                            'Fevereiro',
                            'Mar�o',
                            'Abril',
                            'Maio',
                            'Junho',
                            'Julho',
                            'Agosto',
                            'Setembro',
                            'Outubro',
                            'Novembro',
                            'Dezembro'
                         );

    var $dateFormats = array(
                            I18N_DATETIME_SHORT     =>  'd/m/y',
                            I18N_DATETIME_DEFAULT   =>  'd-M-Y',
                            I18N_DATETIME_MEDIUM    =>  'd-M-Y',
                            I18N_DATETIME_LONG      =>  'd \d\e F \d\e Y',
                            I18N_DATETIME_FULL      =>  'l, d \d\e F \d\e Y'
                        );
    var $timeFormats = array(
                            I18N_DATETIME_SHORT     =>  'H:i',
                            I18N_DATETIME_DEFAULT   =>  'H:i:s',
                            I18N_DATETIME_MEDIUM    =>  'H:i:s',
                            I18N_DATETIME_LONG      =>  'H:i:s T O',
                            I18N_DATETIME_FULL      =>  'H:i T O'
                        );

    /**
     * the NUMBER stuff
     * @var array the same parameters as they have to be passed to the number_format-function
     */
    var $numberFormat = array(
                              I18N_NUMBER_FLOAT   =>  array('2',',','.'),
                              I18N_NUMBER_INTEGER =>  array('0',',','.')
                             );
}
?>