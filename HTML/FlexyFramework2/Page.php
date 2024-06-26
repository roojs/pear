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

class HTML_FlexyFramework2_Page  {
    
    
    /**
    * the main Template name (which can include a body template)
    *
    * @var string template name
    * @access public
    */
    var $masterTemplate = "master.html"; 
    /**
    * the body Template name
    *
    * @var string template name
    * @access public
    */
    var $template = "error.html";
        
   
     
    
    /**
    * cache method - 
    *   can be 'output', or 'data'
    * used to set a default caching method
    *
    * @var string
    * @access public
    * @see getCache()
    */
    var $cacheMethod = '';
    
     
    
    
   /**
    * cache store (PEAR's Cache Object Instance
    *   initialized at start
    *   set at output stage.
    *
    * @var object
    * @access private
    * @see getCache()
    */
   
    var $_cache = NULL; 
    
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
    * rootDir, the base installation directory - can be used to find relative files.
    * (set by page loader)
    * @var string
    * @access public
    */
    var $rootDir; 
    /**
    * the full request string used by the getCacheID().
    * (set by page loader)
    * @var string
    * @access public
    */
    var $request; // clean page request for page
    /**
    * overrides for elements.
    *  
    * @var array
    * @access public
    */
    var $elements = array(); // key=>HTML_Template_Flexy_Element
   
     /**
    * errors for elements
    *  
    * @var array
    * @access public
    */
    var $errors = array(); // key(element name)=>error message
   
   
    /**
    * is the page being run from the command line?
    *  
    * @var bool
    * @access public
    */
    var $cli = false;
    /**
    * Arguments from cli if static $cli_opts is used.
    *  
    * @var array
    * @access public
    */
    var $cli_args = array(); // key(element name)=>error message
   
    /**
     * Reference to the page loader
     * @var type HTML_FlexyFramework - 
     * 
     */
     
    var $bootLoader = false;
   
   
    var $timer;
    var $subrequest;
   
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
        $cli= $this->frameworkOptions()->cli;
        if (!$cli && $isRedirect !== true && !empty($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "POST") {
            return $this->post($request,$args);
        }  
        return $this->get($request,$args,$isRedirect);
        
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
        
        if (!empty($this->cli)) {
            return;
        }
        
        /* output the body if no masterTemplate is set */
        $options = $this->frameworkOptions();
        
        $type = isset($this->contentType) ? $this->contentType : 'text/html'; 
        header('Content-Type: '.$type.';charset='.( empty($options->charset) ? 'UTF-8' : $options->charset ));
        
         
        if (!$this->masterTemplate) {
            return $this->outputBody();
        }
        /* master template */
        
       require_once 'HTML/Template/Flexy.php' ;

        $template_engine = new HTML_Template_Flexy($this->frameworkOptions()->HTML_Template_Flexy);
        $template_engine->compile($this->masterTemplate);
        if (!$this->_cache || !$this->cacheMethod) {
            $template_engine->outputObject($this,$this->elements);
            return;
        }
        
        $id = $this->_cache->generateID($this->getID());
        $this->_cache->save($id, $template_engine->bufferedOutputObject($this,$this->elements));
        echo $this->_cache->get($id);
        
    }
    /**
    * The body Output layer.
    * 
    * compiles the template
    * At present there is no caching in here..  - this may change latter..
    * 
    * used by putting {outputBody} in the main template.
    * @access   public
    */    
    function outputBody($return = false)
    {

        require_once 'HTML/Template/Flexy.php' ;

        $template_engine = new HTML_Template_Flexy($this->frameworkOptions()->HTML_Template_Flexy);
        $template_engine->compile($this->template);
        if ($this->elements) { /* BC crap! */
            require_once 'HTML/Template/Flexy/Factory.php';
            $this->elements = HTML_Template_Flexy_Factory::setErrors($this->elements,$this->errors);
        }
        $template_engine->elements = $this->elements;
        if ($return) {
            return $template_engine->bufferedOutputObject($this,$this->elements);
        }
        $template_engine->outputObject($this,$this->elements);
        
    }
    
     
    
    /**
    * Utility method : get the Class name (used on templates)
    *
    * @return   string   class name
    * @access   public
    */
    
    
    function getClass() {
        return get_class($this);
    }
    /**
    * Utility method : get the Time taken to generate the page.
    *
    * @return   string   time in seconds..
    * @access   public
    */
    
    function getTime() {
         
        $m = explode(' ',microtime());
        $ff =  HTML_FlexyFramework::get();
        return sprintf('%0.2fs',($m[0] + $m[1]) -  $ff->start)
                . '/ Files ' . count(get_included_files());
    
    
    }
    /**
     * turn on off session - wrap long database queries or
     * data processing with this to prevent locking
     * @see
     * @param int $state new session state - 0 = off, 1 = on
     */ 
    
    function sessionState($state)
    { 
        static $ses_status = false;
        static $ini = false;
        
        if (!empty($_SERVER['PHP_AUTH_USER']) ||  php_sapi_name() == "cli") {
            // do not do sessions if we are using http auth.
            return;
        }
        
        // session status is only php5.4 and up..
        if (!defined('PHP_SESSION_ACTIVE')) {
            define('PHP_SESSION_ACTIVE' , 1);
        }
        if(!function_exists('session_status')){
             $ses_status = 1;
        } else {
            $ses_status = ($ses_status === false) ? session_status() : $ses_status;        
        }
        if (PHP_SESSION_ACTIVE != $ses_status) {
            return;
        }
        
        switch ($state) {
            case 0:
                session_write_close();
                return;
            case 1:
                if ($ini) {  
                    ini_set('session.use_only_cookies', false);
                    ini_set('session.use_cookies', false);
                    ini_set('session.use_trans_sid', false);
                    ini_set('session.cache_limiter', null);
                }
                $ini = true;
                // sometimes raises a notice - ps_files_cleanup_dir.
                @session_start();
                $this->dedupeSessionCookies();
                return;
        }
    }
     function dedupeSessionCookies()
    {
         if (headers_sent()) {
            return;
        }
        $cookies = array();
        
        foreach (headers_list() as $header) {
            // Identify cookie headers
            if (strpos($header, 'Set-Cookie:') === 0) {
                $cookies[] = $header;
            }
        }
        header_remove('Set-Cookie');

        // Restore one copy of each cookie
        foreach(array_unique($cookies) as $cookie) {
            header($cookie, false);
        }
    }
    
    function frameworkOptions()
    {
        return HTML_FlexyFramework2::get();
    }
}

 
