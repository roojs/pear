<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Wolfram Kriesing <wolfram@kriesing.de>                      |
// +----------------------------------------------------------------------+
//
//  $Id: determineLanguage.inc.php 110339 2003-01-04 11:55:29Z mj $

        // we make it very simple for now,
        // this should be done using a db one day, either one that "learns" or one which is already a huge dictionary
// FIXXME may be each word should be a regular expression, to catch different
// forms (i.e.: like, likes), this is more relevant for languages other than english
// but regexps may consume much more time when parsing all the languages ...
        $languages = array( 'en' => array(  'the','it','this',
                                            'he','she','him','her','his',
                                            'who','why','that','what',
                                            'with','has','been',
                                            'is','of','from','for')
                            ,'de' => array( 'der','die','das','des','dem',
                                            'er','sie','es','ich','du','wir','ihr',
                                            'warum','wieso','wie','wo','weshalb','was',
                                            'habe','haben','machen','tun','ist')
                            ,'es' => array( 'lo','la','las','los','esto','es',
                                            'el','yo','tu','ella','su','mi','ti',
                                            'por','que','cuanto','cuando','donde',
                                            'para','desde','hasta','luego','por','y','o','con',
                                            'hacer','hace','tener','esta','estar')
                            ,'fr' => array( 'le','la','les',
                                            'je','tu','il','elle','nous','vous','ils','elles','ma','mon','ta','ton','notre','votre',
                                            'por','quoi','quand','qui','ou','combien',
                                            'pour','par','apres','ce','mais','et','ou','oui','non','en','avec',
                                            'suis','est','avoir')

                            // italian provided by: Simone Cortesi <simone@cortesi.com>
                            ,'it' => array( 'il','lo','la','i','gli','le',
                                            'questo','quello',
                                            'io','tu','lui','lei','ella','egli','noi','voi','loro','essi',
                                            'mio','tuo','suo','nostro','vostro',
                                            'chi','perché','perche','quanto','quando','dove',
                                            'di','a','da','in','con','su','per','tra','fra',
                                            'essere','fare','avere')
                            // Polish provided by: Piotr Klaban <makler@man.torun.pl>
                            ,'pl' => array( 'ten', 'ta', 'to', 'tego', 'mnie', 'nami',
                                            'ja', 'ty', 'on', 'ona', 'ono', 'oni', 'my', 'wy',
                                            'dla', 'po', 'przed', 'za', 'miêdzy',
                                            'ale', 'albo', 'czy', 'kto', 'kiedy', 'kiedy¶', 'co', 'dlaczego',
                                            '¿e', '¿eby', 'który', 'która', 'które', 'jak', 'jaki', 'jaka', 'jakie',
                                            'czyj', 'czyja', 'czyje',
                                            'wczoraj', 'dzisiaj', 'jutro',
                                            'jest', 'jestem', 'jeste¶', 'jeste¶my', 'jeste¶cie', 's±',
                                            'ma', 'mia³em', 'mia³', 'by³', 'bêdzie', 'by³em')
                          );

?>
