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
// $Id: FlexyFramework.php,v 1.8 2003/02/22 01:52:50 alan Exp $
//
//  Description
//  A Page (URL) to Object Mapper
//  Cleaned up version.. - for use on new projects -- not BC!! beware!!!


//-----------------------------------------------------------
// Debian APACHE - some idiot disabled AcceptPathInfo - it needs adding back.
//-----------------------------------------------------------

 
 
// Initialize Static Options
require_once 'PEAR.php';
require_once 'HTML/FlexyFramework/Page.php';  
require_once 'HTML/FlexyFramework/Error.php';
// better done here..
require_once 'DB/DataObject.php';

// To be removed ?? or made optional or something..
 

// remove E_ANAL  
 
error_reporting(E_ALL & ~E_STRICT );
//ini_set('display_errors','off');
//ini_set('log_errors','off');

//PEAR::setErrorHandling(PEAR_ERROR_TRIGGER, E_USER_ERROR);




/**
* The URL to Object Mapper
*
* Usage:
* Create a index.php and add these lines.
*  
* ini_set("include_path", "/path/to/application"); 
* require_once 'HTML/FlexyFramework.php';
* HTML_FlexyFramework::factory(array("dir"=>"/where/my/config/dir/is");
*
*
* the path could include pear's path, if you dont install all the pear 
* packages into the development directory.
*
* It attempts to load a the config file from the includepath, 
* looks for ConfigData/default.ini
* or ConfigData/{hostname}.ini
* if your file is called staging.php rather than index.php 
* it will try staging.ini
*
*/
 
class HTML_FlexyFramework {
    
    /**
     * Confirgurable items..
     * If we set them to 'true', they must be set, otherwise they are optional.
     */
    var $project; // base class name
    var $database; // set to true even if nodatabase=true
    
    // optional
    var $debug = false;
    var $enable = false; // modules
    var $disable = false; // modules or permissions
    var $appName = false;
    var $appNameShort = false; // appname (which has templates)
    var $version = false; // give it a version name. (appended to compile dir)
    var $nodatabase = false; // set to true to block db config and testing.
    var $fatalAction = false; // page to redirct to on failure. (eg. databse down etc.)
    var $charset = false; // default UTF8
    var $dataObjectsCache = true;  // use dataobjects ini cache.. - let's try this as the default behaviour...
    var $dataObjectsCacheExpires = 72000; // 20 hours..
    var $languages = false; // language settings -- see _handlelanguage
    var $projectExtends = false; // if this is an array, it's a fallback of 'Projects' that can be called
    

    
    // derived.
    var $cli = false; // from cli 
    var $run = false; // from cli
    var $enableArray = false; // from enable.
    var $classPrefix = false; // from prject.
    var $baseDir = false ; // (directory+project)
    var $rootDir = false ; // (directory that index.php is in!)
    
    var $baseURL = false;
    var $rootURL = false ; // basename($baseURL)
    
    var $page = false; // active page..
    var $timer = false; // the debug timer
    var $calls = false; // the number of calls made to run!
    var $start = false; // the start tiem.
    
    var $baseRequest = '';
    var $ext; // the striped extention.
    
    var $dataObjectsOriginalIni = ''; // 1 houre..
    
    // used to be $_GLOBALS[__CLASS__]
    
    static $singleton; 
    
    
    /**
     * 
     * Constructor - with assoc. array of props as option
     * called by index.php usually, and runs the app code,
     *
     * uses 'universal construcor' format, so the argument relates directly to properties of this object.
     * 
     */
    
    
    function __construct($config)
    {
        if (isset(self::$singleton)) {
            trigger_error("FlexyFramework Construct called twice!", E_ERROR);
        }
        
        self::$singleton = $this;
        
        $this->calls = 0;

        $m = explode(' ',microtime());
        $this->start = $m[0] + $m[1];
        
        foreach($config as $k=>$v) {
            $this->$k = $v;
        }
        $this->_parseConfig();
        
        // echo '<PRE>'; print_r($this);exit;
        if ($this->cli) {
            $args = $_SERVER['argv'];
            array_shift($args );
            array_shift($args );
            $this->_run($this->run,false,$args);
            return;
        }
    
        // handle apache mod_rewrite..
        // it looks like this might not work anymore..
        
        
        
        if (!empty($_SERVER['REDIRECT_URL'])) {
            
            $this->_run($_SERVER['SCRIPT_NAME'] . $_SERVER['REQUEST_URI'],false);
            return ;
        }
        
        
        $this->_run($_SERVER['REQUEST_URI'],false);
            
        
    }
    /**
     * This is the standard way to get information about the application settings.
     * $ff = HTML_FlexyFramework::get();
     * if ($ff->SomeVar[...])....
     *
     */
    static function get()
    {
        return self::$singleton;
    }
  
  
    /**
     * parse the configuration set by the constructor.
     * 
     *
     */
  
    function _parseConfig()
    {
        
        // make sure required values are set.. (anything that is not defaulted to false..)
        foreach(get_class_vars(__CLASS__) as $k =>$v) {
            if ($v === false && !isset($this->$k)) {
                die("$k is not set");
            }
        }
        
        $this->_handleLanguages();
        
        // enable modules.
        if (!empty($this->enable)) {
            $this->enableArray = explode(',', $this->enable);
            
            if (!in_array('Core',$this->enableArray ) &&
                !in_array('Core', explode(',', $this->disable ? $this->disable : '')))
            {
                $this->enable = 'Core,'. $this->enable ;
                $this->enableArray = explode(',', $this->enable);
            }
        }
        // are we running cli?
        $this->cli = php_sapi_name() == 'cli'; 
        
        // will these work ok with cli?
        $bits = explode(basename($_SERVER["SCRIPT_FILENAME"]), $_SERVER["SCRIPT_NAME"]);
        if (!$this->cli) {
            $bits[0] = str_replace('%2F','/',urlencode($bits[0]));
            $this->baseURL = $bits[0] . basename($_SERVER["SCRIPT_FILENAME"]);
            //phpinfo();exit;
            if (empty($_SERVER['SCRIPT_NAME'])) {
                
                $this->baseURL = ''; // ??? this is if we replace top level...
            }
        }
        // if cli - you have to have set baseURL...
        
        
        $this->rootDir = realpath(dirname($_SERVER["SCRIPT_FILENAME"]));
        $this->baseDir = $this->rootDir .'/'. $this->project;
        $this->rootURL = dirname($this->baseURL);
        $this->rootURL = ($this->rootURL == '/') ? '' : $this->rootURL;
        
        
      
        //var_dump($this->baseURL);
        
        if (!isset($this->database) && isset($this->DB_DataObject['database'])) {
            $this->database = $this->DB_DataObject['database'];
        }
        
         $this->classPrefix   = str_replace('/', '_', $this->project) . '_';
        
        // list the available options..
        if ($this->cli && empty($_SERVER['argv'][1])) {
            require_once 'HTML/FlexyFramework/Cli.php';
            $fcli = new HTML_FlexyFramework_Cli($this);
            $fcli->cliHelp();
            exit;
        }
        
        
        // see if it's a framework assignment.
        $ishelp = false;
        if ($this->cli) {
            require_once 'HTML/FlexyFramework/Cli.php';
            $fcli = new HTML_FlexyFramework_Cli($this);
            $res = $fcli->parseDefaultOpts();
            if ($res === true) {
                $ishelp = true;
            } 
             
        }
        
        
        $this->run = $this->cli ? $_SERVER['argv'][1] : false;
     
        
        $this->_parseConfigDataObjects();
        if ($this->dataObjectsCache && !$this->nodatabase) {
            $this->_configDataObjectsCache();
        }
        
        $this->_parseConfigTemplate();
        $this->_parseConfigMail();
 
        //echo '<PRE>';print_r($this);exit;
        
        $this->_exposeToPear();
                

        $this->_validateEnv();
        
        if ($ishelp) {
            return;
        }

        $this->_validateDatabase();
 
        $this->_validateTemplate();
        
    }
    /**
     *
     *
     *'languages' => array(
            'param' => '_lang',
            'avail' => array('en','zh_HK', 'zh_CN'),
            'default' => 'en',
            'cookie' => 'TalentPricing_lang',
            'localemap' => array(
                'en' => 'en_US.utf8',
                'zh_HK' => 'zh_TW.utf8',
                'zh_CN' => 'zh_CN.utf8',
            )
        ),
    */
    function _handleLanguages()
    {
        if (
                empty($this->languages) ||
                (
                        !isset($this->languages['cookie']) && !isset($this->languages['default'])
                )
        ) {
            return;
        }
        
        
        $cfg = $this->languages;
           
        $lang = isset($_COOKIE[$cfg['cookie']]) ?  $_COOKIE[$cfg['cookie']] : $cfg['default'];

        if (isset($_REQUEST[$cfg['param']])) {
            $lang = $_REQUEST[$cfg['param']];
        }
    
        if (!in_array($lang, $cfg['avail'])) {
            $lang = $cfg['default'];
        }
        if (isset($cfg['localemap'][$lang])) {
            setlocale(LC_ALL, $cfg['localemap'][$lang]);
        }
        setcookie($cfg['cookie'], $lang, 0, '/');
        
        $this->locale = $lang;
        
        if (!empty($this->HTML_Template_Flexy)) {
            $this->HTML_Template_Flexy['locale'] = $lang;   //set a language for template engine
        }
         
    }
    
    /**
     * overlay array properties..
     */
    
    function applyIf($prop, $ar) {
        if (!isset($this->$prop)) {
            $this->$prop = $ar;
            return;
        }
        // add only things that where not set!!!.
        $this->$prop = array_merge($ar,$this->$prop);
        
        return;
        //foreach($ar as $k=>$v) {
        //    if (!isset($this->$prop->$k)) {
         //       $this->$prop->$k = $v;
          //  }
       // }
    }
    
    /**
     * DataObject cache 
     * - if turned on (dataObjectsCache = true) then 
     *  a) ini file points to a parsed version of the structure.
     *  b) links.ini is a merged version of the configured link files.
     * 
     * This only will force a generation if no file exists at all.. - after that it has to be called manually 
     * from the core page.. - which uses the Expires time to determine if regeneration is needed..
     * 
     * 
     */
    
    function _configDataObjectsCache()
    {
        // cli works under different users... it may cause problems..
        $this->debug(__METHOD__);
        if (function_exists('posix_getpwuid')) {
            $uinfo = posix_getpwuid( posix_getuid () ); 
            $user = $uinfo['name'];
        } else {
            $user = getenv('USERNAME'); // windows.
        }
        
        

        $iniCache = ini_get('session.save_path') .'/' . 
               'dbcfg-' . $user . '/'. str_replace('/', '_', $this->project) ;
        
        
        if ($this->appNameShort) {
            $iniCache .= '_' . $this->appNameShort;
        }
        if ($this->version) {
            $iniCache .= '.' . $this->version;
        }
        if ($this->database === false) {
            return;
        }
        
        $dburl = parse_url($this->database);
        if (!empty($dburl['path'])) {
            $iniCache .= '-'.ltrim($dburl['path'],'/');
        }
        
        $iniCache .= '.ini';
        $this->debug(__METHOD__ . " : ini cache : $iniCache");
        
        $dburl = parse_url($this->database);
        $dbini = 'ini_'. basename($dburl['path']);
        $this->debug(__METHOD__ . " : ini file : $dbini");
        //override ini setting... - store original..
        if (isset($this->DB_DataObject[$dbini])) {
            $this->dataObjectsOriginalIni = $this->DB_DataObject[$dbini];
            ///print_r($this->DB_DataObject);exit;
        }
        // 
        
        
        
        $this->DB_DataObject[$dbini] =   $iniCache;
        // we now have the configuration file name..
        
        
        if (!file_exists($iniCache) || empty( $this->dataObjectsCacheExpires)) {
            $this->generateDataobjectsCache(true);
            return;
        }
     
        
        
    }
    /**
     *  _generateDataobjectsCache:
     * 
     * create xxx.ini and xxx.links.ini 
     * 
     * @arg force (boolean) force generation - default false;
     * 
     */
     
    function generateDataobjectsCache($force = false)
    {
        //$this->debug('generateDataobjectsCache: force=' . ($force ? 'yes' : 'no'));
        if (!$this->dataObjectsCache) { // does not use dataObjects Caching..
            $this->debug('generateDataobjectsCache', 'dataObjectsCache - empty');
            return;
        }
        
        $dburl = parse_url($this->database);
        $dbini = 'ini_'. basename($dburl['path']);
        
        
        $iniCache = $this->DB_DataObject[$dbini];
        $iniCacheTmp = $iniCache . '.tmp' .md5(rand());  // random to stop two processes using the same file.
        // has it expired..
        $force = ($force ? $force : !file_exists($iniCache)) || !$this->dataObjectsCacheExpires;
        // $this->debug('generateDataobjectsCache: after check : force=' . ($force ? 'yes' : 'no'));
         // not force or not expired, do not bother..
        if (!$force) {
            if ((filemtime($iniCache) + $this->dataObjectsCacheExpires) >time()) {
                return;
            }
        }
        
         //echo "GENERATE?";
        
        // force quoting of column names..
        // unless it forced off..
        if (!isset($this->DB_DataObject['quote_identifiers_tableinfo'] )) { 
            $this->DB_DataObject['quote_identifiers_tableinfo'] = true;
        }
        if (!file_exists(dirname($iniCache))) {
            mkdir(dirname($iniCache),0700, true);
        }
        
        $this->DB_DataObject[$dbini] = $iniCacheTmp;
        $this->_exposeToPear();
        
        
        // DB_DataObject::debugLevel(1);      
        require_once 'HTML/FlexyFramework/Generator.php';
        $generator = new HTML_FlexyFramework_Generator();
        $generator->start();
        
        HTML_FlexyFramework_Generator::writeCache($iniCacheTmp, $iniCache); 
        // reset the cache to the correct lcoation.
        $this->DB_DataObject[$dbini] = $iniCache;
        $this->_exposeToPear();
        
        //$GLOBALS['_DB_DATAOBJECT']['INI'][$this->database] =   parse_ini_file($iniCache, true);
        //$GLOBALS['_DB_DATAOBJECT']['SEQUENCE']
        // clear any dataobject cache..
         
        
        //die("done");
        
    }
    /**
     * DataObject Configuration:
     * Always in Project/DataObjects
     * unless enableArray is available...
     * 
     * 
     * 
     */
    function _parseConfigDataObjects()
    {
        if ($this->nodatabase && !$this->database) {
            return;
        }
        $dburl = parse_url($this->database);
        $dbini = 'ini_'. basename($dburl['path']);
                
        $dbinis =  array(); //array(dirname(__FILE__) . '/Pman/DataObjects/pman.ini');
        $dbreq =  array(); //array( dirname(__FILE__) . '/Pman/DataObjects/');
        $dbcls =  array(); //array('Pman_DataObjects_');

        $project = explode('/',$this->project)[0]; 
        
        if (!empty($this->enableArray)) {
                
            $tops = array_merge( array($project), empty($this->projectExtends) ? array() : $this->projectExtends);
            
            foreach($tops as $td) {
                    
                $bd = $this->rootDir .'/'.$td;
                foreach($this->enableArray as $m) {
                    // look in Pman/MODULE/DataObjects/*
                     if (file_exists($bd.'/'.$m.'/DataObjects')) {
                        $dbinis[] = $bd.'/'.$m.'/DataObjects/'. strtolower($project).'.ini';
                        $dbcls[] = $td.'_'. $m . '_DataObjects_';
                        $dbreq[] = $bd.'/'.$m.'/DataObjects';
                        continue;
                    }
                    // look in MODULE/DataObjects ?? DO WE SUPPORT THIS ANYMORE???
                    if (file_exists($bd.'/../'.$m.'/DataObjects')) {
                        $dbinis[] = $bd.'/../'.$m.'/DataObjects/'. strtolower($project).'.ini';
                        $dbcls[] = $td. '_DataObjects_';
                        $dbreq[] = $bd.'/../'.$m.'/DataObjects';
                    }
                        
                        
                      
                }
            }     
        } else {
            
            if (isset($this->DB_DataObject['schema_location'])) {
                $dbinis[] = $this->DB_DataObject['schema_location'] .'/'.basename($dburl['path']).'.ini';
            } else {
                $dbinis[] = $this->baseDir.'/DataObjects/'.basename($dburl['path']).'.ini';
            }
            // non modular.
            
            $dbcls[] = $project .'_DataObjects_';
            $dbreq[] = $this->baseDir.'/DataObjects';
        }
            
        
        $this->applyIf('DB_DataObject', array(   
        
            'class_location' =>  implode(PATH_SEPARATOR,$dbreq),
            'class_prefix' =>  implode(PATH_SEPARATOR,$dbcls),
            'database'        => $this->database,    
            ///'require_prefix' => 
         //   'schema_location' => dirname(__FILE__) . '/Pman/DataObjects/',
             $dbini=> implode(PATH_SEPARATOR,$dbinis),
         
           //   'debug' => 5,
        ));
      //  print_r($this->DB_DataObject);exit;
    }
    /**
     Set up thetemplate
     * 
     */
    function _parseConfigTemplate()
    {
        
        // compile.
        if (function_exists('posix_getpwuid')) {
            $uinfo = posix_getpwuid( posix_getuid () ); 
         
            $user = $uinfo['name'];
        } else {
            $user = getenv('USERNAME'); // windows.
        }
        
        $compileDir = ini_get('session.save_path') .'/' . 
            $user . '_compiled_templates_' . $this->project;
        
        if ($this->appNameShort) {
            $compileDir .= '_' . $this->appNameShort;
        }
        if ($this->version) {
            $compileDir .= '.' . $this->version;
        }
        
        // templates. -- all this should be cached!!!
        $src = array();
         
        
        if ($this->appNameShort && !in_array('Core', explode(',', $this->disable ? $this->disable : ''))) {
            // in app based version, template directory is in Core
            
            $src = array(  
                $this->baseDir . '/Core/templates'
            );
        }
        
        if(!empty($this->projectExtends)){
            foreach ($this->projectExtends as $e){
                $add = $this->rootDir . '/' . $e .'/templates';
                if (!in_array($add,$src) && file_exists($add)) {
                    $src[] = $add;
                }
            }
        }
        
        $src[] = $this->baseDir . '/templates';
        
        
        
        if (!empty($this->enableArray)) {
             
            
            foreach($this->enableArray as $m) {
                $add = $this->baseDir . '/' . $m .'/templates';
                if (!in_array($add,$src) && file_exists($add) && $this->appNameShort != $m) {
                    $src[] = $add;
                }
                
            }
            if (!empty($this->projectExtends)  )  {
                foreach ($this->projectExtends as $extend){
                    foreach($this->enableArray as $m) {
                        $add = $this->rootDir . '/' . $extend . '/' . $m .'/templates';
                        if (!in_array($add,$src) && file_exists($add) && $this->appNameShort != $m) {
                            $src[] = $add;
                        }
                    }
                }
    
            }
        }
         
        
        if ($this->appNameShort) {
            $src[] =  $this->baseDir . '/'. $this->appNameShort. '/templates';
        }
        
        // images may come from multiple places: - if we have multiple template directories.?
        // how do we deal with this..?
        // images/ << should always be mapped to master!
        // for overridden appdir ones we will have to se rootURL etc.
        
        $url_rewrite = 'images/:'. $this->rootURL . '/'. $this->project. '/templates/images/';
        
        $this->applyIf('HTML_Template_Flexy', array(
            'templateDir' => implode(PATH_SEPARATOR, $src),
            'compileDir' => $compileDir,
            'multiSource' => true,
            'forceCompile' => 0,
            'url_rewrite' => $url_rewrite,
            'filters' => 'Php,SimpleTags', /// for non-tokenizer version?
            'debug' => $this->debug ? 1 : 0,
            'useTokenizer' => 1,
             
            
        
        
        ));
    } 
    
    function _parseConfigMail()
    {
        $this->applyIf('HTML_Template_Flexy', array(
           'debug' => 0,
           'driver' => 'smtp',
           'host' => 'localhost',
           'port' => 25,
        ));
    }
    function _exposeToPear()
    {
        $cls = array_keys(get_class_vars(__CLASS__));
        $base = array();
        
        // anything that get's set, that's not in our default properties
        // is assumed to be an option set .
        foreach(get_object_vars($this) as $k=>$v) {
            if (in_array($k,$cls)) {
                $base[$k] = $v;
                continue;
            }
            $options = &PEAR::getStaticProperty($k,'options');
            $options = $v;
        }
        $options = &PEAR::getStaticProperty('HTML_FlexyFramework','options');
        $options = $base;
         //   apply them..
    }
    
    
    function _validateEnv() 
    {
        /* have I been initialized */
        
        
        if (get_magic_quotes_gpc() && !$this->cli) {
            $this->fatalError(
                "magic quotes is enabled add the line<BR>
                   php_value magic_quotes_gpc 0<BR>
                   to your .htaccess file <BR>
                   (Apache has to be configured to &quot;AllowOverride Options AuthConfig&quot; for the directory)
                   ");
                
        }
        // set up error handling - 
        $this->error = new HTML_FlexyFramework_Error();
        
        /// fudge work around bugs in PEAR::setErrorHandling(,)
        $GLOBALS['_PEAR_default_error_mode'] = PEAR_ERROR_CALLBACK;
        $GLOBALS['_PEAR_default_error_options'] = array($this->error,'raiseError');
        
        
        
        if ($this->debug) {
            require_once 'Benchmark/Timer.php'; 
            $this->timer = new BenchMark_Timer(true);
            register_shutdown_function(function() { echo $this->timer->getOutput(); });
        }

    }
    
    function _validateDatabase()
    {
        //echo "<PRE>"; print_r($this);

        if ($this->nodatabase) {
            return;
        }
        $options = &PEAR::getStaticProperty('DB_DataObject','options');
        $dd = empty($options['dont_die']) ? false : true;
        $options['dont_die'] = true;
        
        // database is the only setting - we dont support mult databses?
          
            
        $x = new DB_Dataobject;
        $x->_database = $this->database;
        if (PEAR::isError($err = $x->getDatabaseConnection())) {
                                

                $this->fatalError("Configuration or Database Error: could not connect to Database, <BR>
                    Please check the value given to HTML_FlexyFramework, or run with debug on!<BR>
                     <BR> ".$err->toString());
        }
        // reset dont die!
        $options['dont_die'] = $dd ;
        
        
    }
    function _validateTemplate()
    {
        // check that we have a writeable directory for flexy's compiled templates.
        
        if (empty($this->HTML_Template_Flexy['compileDir'])) {
            return;
        }
        
        if ( !file_exists($this->HTML_Template_Flexy['compileDir']))  {
            mkdir($this->HTML_Template_Flexy['compileDir'], 0700);
            @mkdir($this->HTML_Template_Flexy['compileDir'], 0700, true);
            clearstatcache();
             
            if ( !file_exists($this->HTML_Template_Flexy['compileDir']))  {
            
                $this->fatalError("Configuration Error: you specified a directory that does not exist for<BR>
                    HTML_Template_Flexy => compileDir  {$this->HTML_Template_Flexy['compileDir']}<BR>\n"
                );
            }
        }
        
        if (!is_writeable($this->HTML_Template_Flexy['compileDir'])) {
            $this->fatalError("Configuration Error: Please make sure the template cache directory is writeable<BR>
                    eg. <BR>
                    chmod 700 {$this->HTML_Template_Flexy['compileDir']}<BR>
                    chgrp apache_user  {$this->HTML_Template_Flexy['compileDir']}<BR>\n"
            );
        }
        //echo "<PRE>";print_R($config);
        
        
         
          
        
        
    }
  
  
   
        
    
    
    /**
    * Quality Redirector
    *
    * Usage in a page.:
    * HTML_FlexyFramework::run('someurl/someother',array('somearg'=>'xxx'));
    * ...do clean up...
    * exit; <- dont cary on!!!!
    *
    * You should really
    * 
    * @param   string           redirect to url 
    * @param   array Args Optional      any data you want to send to the next page..
    * 
    *
    * @return   false
    * @access   public
    * @static
    */
  
    
    static function run($request,$args=array()) 
    {
        self::$singleton->_run($request,true,$args);
        return false;
    }
    
    
    /**
    * The main execution loop
    *
    * recursivly self called if redirects (eg. return values from page start methods)
    * 
    * @param   string from $_REQUEST or redirect from it'self.
    * @param   boolean isRedirect  = is the request a redirect 
    *
    *
    * @return   false || other    false indicates no page was served!
    * @access   public|private
    * @see      see also methods.....
    */
  
    function _run($request,$isRedirect = false,$args = array()) 
    {
        
        // clean the request up.
        $this->calls++;
        
        if ($this->calls > 5) {
            // to many redirections...
            trigger_error("FlexyFramework:: too many redirects - backtrace me!",E_USER_ERROR);
            exit;
        }
        
        $newRequest = $this->_getRequest($request,$isRedirect);
        
        // find the class/file to load
        list($classname,$subRequest) = $this->requestToClassName($newRequest,FALSE);
        
        
        $this->debug("requestToClassName return = CLASSNAME: $classname SUB REQUEST: $subRequest");
        
        // assume that this was handled by getclassname ?????
        if (!$classname) {
            return false;
        }
        
        // make page data/object accessable at anypoint in time using  this
        // not sure if this is used anymore - or even works..?
        $classobj = &PEAR::getStaticProperty('HTML_FlexyFramework', 'page');
        
        $classobj =  new  $classname();  // normally do not have constructors.
        
        
        $classobj->baseURL = $this->baseURL;
        $classobj->rootURL = $this->rootURL;
        $classobj->rootDir = $this->rootDir;
        $classobj->bootLoader  = $this;
        $classobj->request = $newRequest;
        $classobj->timer = &$this->timer;
        
        $this->page = $classobj;
        if ($this->cli && !$isRedirect ) { // redirect always just takes redirect args..
            require_once 'HTML/FlexyFramework/Cli.php';
            $fcli = new HTML_FlexyFramework_Cli($this);
            $nargs = $fcli->cliParse($classname);
            $args = $nargs === false ? $args : $nargs; /// replace if found.
            $classobj->cli_args = $nargs;
        }
        
        // echo '<PRE>'; print_r($this);exit;
        // echo "CHECK GET AUTH?";
        if (!method_exists($classobj, 'getAuth')) {
        //    echo "NO GET AUTH?";
            $this->fatalError("class $classname does not have a getAuth Method");
            return false;
        }
        
        /* check auth on the page */
        if (is_string($redirect = $classobj->getAuth())) {
            $this->debug("GOT AUTH REDIRECT".$redirect);
            return $this->_run($redirect,TRUE);
        }
        // used HTML_FlexyFramework::run();
                 

        if ($redirect === false) {
            $this->debug("GOT AUTH FALSE");    
            return false; /// Access deined!!! - 
        }
     
        // allow the page to implement caching (for the full page..)
        // although normally it should implement caching on the outputBody() method.
        
        if (method_exists($classobj,"getCache")) {
            if ($result = $classobj->getCache()) {
                return $result;
            }
        }
        /* allow redirect from start */
        if (method_exists($classobj,"start")) {
            if (is_string($redirect = $classobj->start($subRequest,$isRedirect,$args)))  {
                $this->debug("REDIRECT $redirect <BR>");
                return $this->_run($redirect,TRUE);
            }
            if ($redirect === false) {
                return false;
            }
        }
                

         // used HTML_FlexyFramework::run();
        
        /* load the modules 
         * Modules are common page components like navigation headers etc.
         * that can have dynamic code.
         * Code has been removed now..
         */
        
        
        if ($this->timer) {
            $this->timer->setMarker("After $request loadModules Modules"); 
        }
        
        /* output it  - (our base page does not implement output for cli. */
        
        if ( method_exists($classobj,'output')) {
            $classobj->output(); 
        }
        
        
        if ($this->timer) {
            $this->timer->setMarker("After $request output"); 
            $this->timer->stop(); //?? really - yes...
           
            
        }
        
        if ($this->cli) {
            return true;
        }
        
        
        exit; /// die here...
        
    }
    
    /**
    * map the request into an object and run the page.
    *
    * The core of the work is done here.
    * 
    * 
    * @param   request  the request string
    * @param   boolean isRedirect - indicates that it should not attempt to strip the .../index.php from the request.
    * 
    * @access  private
    */
  
    function _getRequest($request, $isRedirect) 
    {
        
        
        
        if ($this->cli) {
            return $request;
        }
        
        $startRequest = $request;
        $request =@ array_shift(explode('?', $request));
        $this->debug("INPUT REQUEST $request<BR>");
        if (!$isRedirect) {
            // check that request forms contains baseurl????
             
            $subreq = substr($request,0,strlen($this->baseURL));
            if ($subreq != substr($this->baseURL,0,strlen($subreq))) {
                $this->fatalError(
                    "Configuration error: Got base of $subreq which does not 
                        match configuration of: $this->baseURL} ");
            }
            $request = substr($request,strlen($this->baseURL));
             
        }
        // strip front
        // echo "REQUEST WAS: $request<BR>";
        // $request = preg_replace('/^'.preg_quote($base_url,'/').'/','',trim($request));
        // echo "IS NOW: $request<BR>";
        // strip end
        // strip valid html stuff
        //$request = preg_replace('/\/[.]+/','',$request);
        

        $request = preg_replace('/^[\/]*/','',$request);
        $request = preg_replace('/\?.*$/','',$request);
        $request = preg_replace('/[\/]*$/','',$request);
        $this->baseRequest = $request;
        $request = str_replace('&','',$request); // any other invalid characters???
        $request = preg_replace('/\.([a-z]+)$/','',$request);
        $this->ext = substr($this->baseRequest , strlen($request));
        
        // REDIRECT ROO to index.php! for example..
        
        if (!$request && !$isRedirect) {
            if ($this->baseURL && (strlen($startRequest) < strlen($this->baseURL))) {
                // needs to handle https + port
                $http = ((!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]  == 'on')) ? 'https' : 'http';
                $sp = '';
                if (!empty($_SERVER['SERVER_PORT'])) {
                    if ((($http == 'http') && ($_SERVER['SERVER_PORT'] == 80)) || (($http == 'https') && ($_SERVER['SERVER_PORT'] == 443))) {
                        // standard ports..
                    } else {
                        $sp .= ':'.((int) $_SERVER['SERVER_PORT']);
                    }
                }
                $host = !empty($_SERVER["HTTP_X_FORWARDED_HOST"]) ? $_SERVER["HTTP_X_FORWARDED_HOST"] : $_SERVER["HTTP_HOST"];
                header('Location: '.$http.'://'.$host .$sp . $this->baseURL);
 
                exit;
            }
            $request = "";
        }
        $this->debug("OUTPUT REQUEST $request<BR>");
        return $request;
    }
    
   
    
    
    /**
    * get the Class name and filename to load
    *
    * Parses the request and converts that into a File + Classname
    * if the class doesnt exist it will attempt to find a file below it, and
    * call that one with the data.
    * Used by the module loader to determine the location of the modules
    *   
    * @param   request  the request string
    * @param   boolean showError - if false, allows you to continue if the class doesnt exist.
    * 
    *
    * @return   array classname, filepath
    * @access   private
    * @static
    */
  
    function requestToClassName($request,$showError=TRUE) 
    {
       // if ($request == "error") {
       //     return array("HTML_FlexyFramework_Error","");
       // }
        
        // special classes ::
        if ($this->cli && in_array($request, array('DataObjects'))) {
            require_once 'HTML/FlexyFramework/'. $request . '.php';
            return array('HTML_FlexyFramework_'. $request,'');
        }
        
        
        $request_array=explode("/",$request);
        $original_request_array = $request_array;
        $sub_request_array = array();
        $l = count($request_array)-1;
        if ($l > 10) { // ?? configurable?
            //PEAR::raiseError("Request To Long");
            $this->fatalError("Request To Long - " . $request);
        }

        
        $classname='';
        // tidy up request array
        
        if ($request_array) {
            foreach(array_keys($request_array) as $i) {
                $request_array[$i] = preg_replace('/[^a-z0-9]/i','_',urldecode($request_array[$i]));
            }
        }
        //echo "<PRE>"; print_r($request_array);
        // technically each module should do a check here... similar to this..
        
        
        for ($i=$l;$i >-1;$i--) {
            $location = implode('/',$request_array) . ".php";
            print_R($location);exit;
            if ($location == '.php') {
                $this->debug("SKIP first path check, as request str is empty");
                break;
            }
            
            $this->debug("baseDIR = {$this->baseDir}");
            
            $floc = "{$this->baseDir}/$location";
            $this->debug("CHECK LOCATION = $location");
            
            
            
            if (!empty($location) && $location != '.php' && @file_exists($floc )) {             // hide? error???
                require_once $floc ;
                $classname = $this->classPrefix . implode('_',$request_array);
                $this->debug("FOUND FILE - SET CLASS = $classname <BR>");
                break;
            } 
            
            // in here check the 'projectExtends' versions..?
            
            if(!empty($this->projectExtends)){
                $this->debug("Trying project Extends<BR>");
                $has_extend_class = false;
                
                foreach ($this->projectExtends as $e){
                    $floc = "{$this->rootDir}/{$e}/$location";
                    $this->debug("Trying file: $floc");
                    if (!empty($location) && @file_exists($floc)) {             // hide? error???
                        require_once $floc ;
                        $classname = $e . '_' . implode('_',$request_array);
                        $has_extend_class = true;
                        $this->debug("FOUND FILE - SET CLASS = $classname <BR>");
                        break;
                    } 
                }
                
                if(!empty($has_extend_class)){
                    break;
                }
                
            }
            
            
            $this->debug("$floc  - !!FOUND NOT FILE!!");
            
            $sub_request_array[] = $original_request_array[$i];
            unset($request_array[$i]);
            unset($original_request_array[$i]);
        }
         
        // is this really needed here!
        
        $classname = preg_replace('/[^a-z0-9]/i','_',$classname);
        $this->debug("CLASSNAME is '$classname'");
        // got it ok.
        if ($classname && class_exists($classname)) {
            $this->debug("using $classname");
            //print_r($sub_request_array);
            return array($classname,implode('/',array_reverse($sub_request_array)));
        }
        // stop looping..
        if ($showError) {
            $this->fatalError("INVALID REQUEST: \n $request FILE:".$this->baseDir. "/{$location}  CLASS:{$classname}");
            
        } 
        
        
        $this->debug("Try base {$this->baseDir}.php");   
        // try {project name}.php
        // this used to be silenced @ - if this fails we are usually pretty fried..
        
        if (file_exists($this->baseDir.'.php')) {
            
            
            $classname = str_replace('/', '_', $this->project); //   basename($this->baseDir);
            
            $this->debug("FOUND {$this->baseDir} requring and checking class $classname");   
            require_once $this->baseDir.'.php';
            $this->debug("require success");
            
            if (!class_exists($classname)) {
                $this->fatalError( "{$this->baseDir}.php did not contain class $classname");
            }
        }
        // got projectname.php
        if ($classname && class_exists($classname)) {
            $this->debug("using $classname");
            //print_r($sub_request_array);
             
            return array($classname,implode('/',array_reverse($sub_request_array)));
        }    
            
        
        $this->fatalError( "can not find {$this->baseDir}.php"); // dies..
              
     
    }
    
    /**
    * ensure Single CLi process 
    * usage:
    * HTML_FlexyFramework::ensureSingle(__FILE__, $this);
    * @param string filename of running class
    * @param object class
    */
      
    static function ensureSingle($sig, $class) 
    {
        //echo "check single: $sig / ". get_class($class) ."\n";
        $ff = HTML_FlexyFramework::get();
        if (function_exists('posix_getpwuid')) {
            $uinfo = posix_getpwuid( posix_getuid () ); 
            $user = $uinfo['name'];
        } else {
            $user = getenv('USERNAME'); // windows.
        }
        $fdir = ini_get('session.save_path') .'/' . 
                $user . '_cli_' . $ff->project ;
     
        
        if (!file_exists($fdir)) {
            mkdir($fdir, 0777);
        }
        
        $lock = $fdir.'/'. md5($sig) . '.' . get_class($class);
        //echo "check single: lock : $lock\n";
        if (!file_exists($lock)) {
            file_put_contents($lock, getmypid());
            //echo "check single: lock : DOES NOT EXIST\n";
            return true;
        }
        $oldpid = file_get_contents($lock);
        if (!file_exists('/proc/' . $oldpid)) {
            
            file_put_contents($lock, getmypid());
          //  echo "check single: lock : PROC NOT EXIST\n";
            return true;
        }
        // file exists, but process might not be the same..
        $name = array_pop(explode('_', get_class($class)));
        $cmd = file_get_contents('/proc/' . $oldpid.'/cmdline');
        if (!preg_match('/php/i',$cmd) || !preg_match('/'.$name.'/i',$cmd)) {
            file_put_contents($lock, getmypid());
            //echo "check single: lock : CMDLINE !have PHP \n";
            return true;
        }
        die("process " . $sig . " already running\n");
        
    }
    /**
     * removes the lock for the applicaiton - use with care...
     *
     *
     */
    static function ensureSingleClear($sig, $class)
    {
        $ff = HTML_FlexyFramework::get();
        if (function_exists('posix_getpwuid')) {
            $uinfo = posix_getpwuid( posix_getuid () ); 
            $user = $uinfo['name'];
        } else {
            $user = getenv('USERNAME'); // windows.
        }
        $fdir = ini_get('session.save_path') .'/' . 
                $user . '_cli_' . $ff->project ;
     
        
        if (!file_exists($fdir)) {
            mkdir($fdir, 0777);
        }
        $lock = $fdir.'/'. md5($sig);
        if (!file_exists($lock)) {
            
            return true;
        }
        unlink($lock);;
    }
    
    
    /**
    * Debugging 
    * 
    * @param   string  text to output.
    * @access   public
    */
  
    function debug($output) {
       
        if (empty($this->debug)) {  
            return;
        }
        echo $this->cli ? 
              "HTML_FlexyFramework::debug  - ".$output."\n" 
            : "<B>HTML_FlexyFramework::debug</B> - ".$output."<BR>\n";
    
    }
    /**
    * Raises a fatal error. - normally only used when setting up to help get the config right.
    * 
    * can redirect to fatal Action page.. - hoepfully not issued before basic vars are set up..
    * 
    * @param   string  text to output.
    * @access   public
    */
    
    function fatalError($msg,$showConfig = 0) 
    {
        
        
         if ($this->fatalAction) {
            HTML_FlexyFramework::run($this->fatalAction,$msg);
            exit;
        }
        
        echo $this->cli ? $msg ."\n" : "<H1>$msg</H1>configuration information<PRE>";
        if ($showConfig) {
            
            print_r($this);
        }
        $ff = HTML_FlexyFramework::get();
        $ff->debug($msg);
        exit;
    }    
}

 