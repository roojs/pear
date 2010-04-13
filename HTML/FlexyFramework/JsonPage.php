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
// $Id: Page.php,v 1.5 2003/02/22 01:52:50 alan Exp $
//
// A Base Page Class for use with HTML_Template_Flexy 
// You could write one of these which used another template engine.
//



/**
* The Base Page class - extend and override the methods to implement your own pages.
*
*
*/

class HTML_FlexyFramework_JsonPage  {
    
    
     
    
    /* ---- Variables set by Page loader -------- */
    
   
        
    /**
    * baseURL, that can be prefixed to URL's to ensure that they correctly relate to application
    * (set by page loader)
    * @var string
    * @access public
    */
    var $baseURL; 
    /**
    * rootURL, the base installation directory - can be used to get images directories.
    * (set by page loader)
    * @var string
    * @access public
    */
    var $rootURL; 
 
    /**
    * the full request string used by the getCacheID().
    * (set by page loader)
    * @var string
    * @access public
    */
    var $request; // clean page request for page
     /**
    * Authentication Check method
    * Override this with a straight return for pages that do not require authentication
    *
    * By default 
    *   a) redirects to login if authenticaiton fails
    *   b) checks to see if a isAdmin method exists on the auth object
    *       if it does see if the user is admin and let them in.
    *       otherwise access denied error is raised
    *   c) lets them in.
    * 
    * 
    *
    * @return   none or string redirection to another page.
    * @access   public
    */
 
    function getAuth() {
         
        
        return false;
        
    }
     
    /**
    * The default page handler
    * by default relays to get(), or post() methods depending on the request.
    *
    * Override this if you do not handle get or post differently.
    * 
    * 
    * @param   string  request, the remainder of the request not handled by the object.
    *
    * @return   none|string none = handled, string = redirect to another page = eg. data/list
    * @access   public
    */
  
    function start($request,$isRedirect=false,$args=array()) 
    { 
        if (!$isRedirect && !empty($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "POST") {
            return $this->post($request,$args);
        } else {
            return $this->get($request,$args);
        }
    }
    /**
    * The get page handler
    *
    * Override this if you want to handle get requests 
    * 
    * 
    * @param   string  request, the remainder of the request not handled by the object.
    *
    * @return   none|string none = handled, string = redirect to another page = eg. data/list
    * @access   public
    */
    function get($request) 
    {
    }
     /**
    * The post page handler
    *
    * Override this if you want to handle get requests 
    * 
    * 
    * @param   string  request, the remainder of the request not handled by the object.
    *
    * @return   none|string none = handled, string = redirect to another page = eg. data/list
    * @access   public
    */
   function post($request) 
   {
   }
   
   
    function jerr($str, $errors=array()) // standard error reporting..
    {
        
        if (isset($_SERVER['CONTENT_TYPE']) && preg_match('#multipart/form-data#i', $_SERVER['CONTENT_TYPE'])) {
            header('Content-type: text/html');
            echo "<HTML><HEAD></HEAD><BODY>";
            echo  json_encode(array(
                    'success'=> false, 
                    'errorMsg' => $str,
                     'message' => $str, // compate with exeption / loadexception.

                    'errors' => $errors ? $errors : true, // used by forms to flag errors.
                    'authFailure' => !empty($errors['authFailure']),
                ));
            echo "</BODY></HTML>";
            exit;
        }
        header('Content-type: application/json');
        echo json_encode(array(
            'success'=> false, 
            'data'=> array(), 
            'errorMsg' => $str,
            'message' => $str, // compate with exeption / loadexception.
            'errors' => $errors ? $errors : true, // used by forms to flag errors.
            'authFailure' => !empty($errors['authFailure']),
        ));
        exit;
        
    }
    function jok($str)
    {
        
        
        if (isset($_SERVER['CONTENT_TYPE']) && preg_match('#multipart/form-data#i', $_SERVER['CONTENT_TYPE'])) {
            header('Content-type: text/html');
            echo "<HTML><HEAD></HEAD><BODY>";
            echo  json_encode(array('success'=> true, 'data' => $str));
            echo "</BODY></HTML>";
            exit;
        }
        header('Content-type: application/json');
        echo json_encode(array('success'=> true, 'data' => $str));
        exit;
        
    }
    function jdata($ar,$total=false, $extra=array())
    {
        
        if ($total == false) {
            $total = count($ar);
        }
        $extra=  $extra ? $extra : array();
        header('Content-type: application/json');
        echo json_enocde(array('success' =>  true, 'total'=> $total, 'data' => $ar) + $extra);
        exit;
        
    }
    
    
   
   
    
     
    /**
    * The master Output layer.
    * 
    * compiles the template
    * if no caching - just runs the template.
    * otherwise stores it in the cache.
    * 
    * you dont normally need to override this.
    * 
    * called by the page loader.
    * @access   public
    */
    
    
    
    function output() 
    {
        $this->jerr("Output staged reached - you should output in get/post");
        
    }
      
    
}

 
