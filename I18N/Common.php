<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
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
// | Authors: Naoki Shima <murahachibu@php.net>                           |
// |                                                                      |
// +----------------------------------------------------------------------+//
// $Id: Common.php 112735 2003-01-19 20:02:50Z cain $

require_once('PEAR.php');

/**
*
*   @package    I18N
*
*/
class I18N_Common extends PEAR {

    // {{{ properties
    /**
     * @type  : array
     * @access: private
     */
    var $_codes;

    // }}}
    // {{{ constructor
   
    /**
     * Call parent::PEAR() for destuctor to be called, and initialize vars
     *
     * @return: void
     * @access: public
     */
    function I18N_Common()
    {
        parent::PEAR();
        $this->_codes = array();
    }

    // }}}
    // {{{ _constructor()
   
    /**
     * Dummy constructor
     *
     * @access: private
     * @return: void
     */
    function _constructor()
    {
        $this->I18N_Common();
    }

    // }}}
    // {{{ destructor
   
    /**
     * It does nothing now
     *
     * @access: private
     * @return: void
     */
    function _I18N_Common()
    {
    }

    // }}}
    // {{{ getAllCodes()
   
    /**
     * Return all the codes. Used by child classes.
     *
     * @return: array
     * @access: public
     */
    function getAllCodes()
    {
        return $this->_codes;
    }

    // }}}
}
?>
