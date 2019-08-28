<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors:  Alan Knowles <alan@akbkhome.com>                           |
// +----------------------------------------------------------------------+
//
// $Id: Error.php,v 1.3 2003/02/22 01:52:50 alan Exp $
//
// The simple error handler. 
//


require_once 'HTML/FlexyFramework/Page.php';


/**
* Simple Error handler
*
*
* Currenly just displays a page error.html when you redirect to it.
* eg. in your start or get methods do 
*
* PEAR::RaiseError('some error message');
* return 'Error';
* 
*
* Needs to log errors really..
*
*
*/

class HTML_FlexyFramework_Error extends HTML_FlexyFramework_Page {
        
    /**
    * The error page (this should really be configurable using the config file)
    *
    * done by overriding the template body..
    * @var string
    * @access public
    */
    var $template = "error.html";

    /**
    * Override getAuth to let everyone see the error page.
    * @access public
    */
  
    function getAuth() 
    {
        return;
    }
     /**
    * Override start method to set the $this->error_message to the last recorded error
    * @access public
    */
  
    function start($request,$isRedirect=false,$args=array()) 
    {
        //echo "Running error page";
        $error = &PEAR::getStaticProperty('pages_error','error');
       
        $this->errorMessage = "unknown error";
        if ($error) {
            $this->errorMessage = $error->getMessage();
        }
        
    }
    /**
    * Store the last Recorded Error message in PEAR::getStaticProperty('pages_error','error');
    * This is the default hanlder for all pear errors (As set up in the framework class)
    *
    * @param pearError
    * @access public
    */
    function raiseError($newError) {
        
        $error = &PEAR::getStaticProperty('pages_error','error');
        $error = $newError;
    }
    /**
    * Fetch the last error object
    * @return object PEAR error object
    * @access public
    */
    function getError() {
        return PEAR::getStaticProperty('pages_error','error');
    }
    
}
 