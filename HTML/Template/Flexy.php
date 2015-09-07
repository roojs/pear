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
// | Author:  Alan Knowles <alan@akbkhome.com>
// | Original Author: Wolfram Kriesing <wolfram@kriesing.de>             |
// +----------------------------------------------------------------------+
//
 
/**
*   @package    HTML_Template_Flexy
*/
// prevent disaster when used with xdebug! 
@ini_set('xdebug.max_nesting_level', 1000);

/*
* Global variable - used to store active options when compiling a template.
*/
$GLOBALS['_HTML_TEMPLATE_FLEXY'] = array(); 

// ERRORS:

/**
 * Workaround for stupid depreciation of is_a...
 */
function HTML_Template_Flexy_is_a($obj, $class)  // which f***wit depreciated is_a....
{
    if (version_compare(phpversion(),"5","<")) {
       return is_object($obj) &&  is_a($obj, $class);
       
    } 
    $test=false; 
    @eval("\$test = \$obj instanceof ".$class.";");
    return $test;

}



define('HTML_TEMPLATE_FLEXY_ERROR_SYNTAX',-1);  // syntax error in template.
define('HTML_TEMPLATE_FLEXY_ERROR_INVALIDARGS',-2);  // bad arguments to methods.
define('HTML_TEMPLATE_FLEXY_ERROR_FILE',-2);  // file access problem

define('HTML_TEMPLATE_FLEXY_ERROR_RETURN',1);  // RETURN ERRORS
define('HTML_TEMPLATE_FLEXY_ERROR_DIE',8);  // FATAL DEATH
/**
* A Flexible Template engine - based on simpletemplate  
*
* @abstract Long Description
*  Have a look at the package description for details.
*
* usage: 
* $template = new HTML_Template_Flexy($options);
* $template->compiler('/name/of/template.html');
* $data =new StdClass
* $data->text = 'xxxx';
* $template->outputObject($data,$elements)
*
* Notes:
* $options can be blank if so, it is read from 
* PEAR::getStaticProperty('HTML_Template_Flexy','options');
*
* the first argument to outputObject is an object (which could even be an 
* associateve array cast to an object) - I normally send it the controller class.
* the seconde argument '$elements' is an array of HTML_Template_Flexy_Elements
* eg. array('name'=> new HTML_Template_Flexy_Element('',array('value'=>'fred blogs'));
* 
*
*
*
* @version    $Id: Flexy.php 315533 2011-08-26 02:39:02Z alan_k $
*/



class HTML_Template_Flexy  
{

    /* 
    *   @var    array   $options    the options for initializing the template class
    */
    var $options = array(   
        'compileDir'    =>  '',         // where do you want to write to.. (defaults to session.save_path)
        'templateDir'   =>  '',         // where are your templates
        
        // where the template comes from. ------------------------------------------
        'multiSource'   => false,       // Allow same template to exist in multiple places
                                        // So you can have user themes....
        'templateDirOrder' => '',       // set to 'reverse' to assume that first template
        
         
        'debug'         => false,       // prints a few messages
        
        
        // compiling conditions ------------------------------------------
        'compiler'      => 'Flexy',  // which compiler to use. (Flexy,Regex, Raw,Xipe)
        'forceCompile'  =>  false,      // only suggested for debugging
        'dontCompile'	=> false,		// never compile - use this if you're manually compiling your templates

        // regex Compiler       ------------------------------------------
        'filters'       => array(),     // used by regex compiler.
        
        // standard Compiler    ------------------------------------------
        'nonHTML'       => false,       // dont parse HTML tags (eg. email templates)
        'allowPHP'      => false,       // allow PHP in template (use true=allow, 'delete' = remove it.)
        
        'flexyIgnore'   => 0,           // turn on/off the tag to element code
        'numberFormat'  => ",2,'.',','",  // default number format  {xxx:n} format = eg. 1,200.00 
        
        'url_rewrite'   => '',          // url rewriting ability:
                                        // eg. "images/:test1/images/,js/:test1/js"
                                        // changes href="images/xxx" to href="test1/images/xxx"
                                        // and src="js/xxx.js" to src="test1/js/xxx.js"
                                        
        'compileToString' => false,     // should the compiler return a string 
                                        // rather than writing to a file.
        'privates'      => false,       // allow access to _variables (eg. suido privates
        'globals'       => false,       // allow access to _GET/_POST/_REQUEST/GLOBALS/_COOKIES/_SESSION

        'globalfunctions' => false,     // allow GLOBALS.date(#d/m/Y#) to have access to all PHP's methods
                                        // warning dont use unless you trust the template authors
                                        // exec() becomes exposed.
        'useElementLabels' => true,     // WARNING DO NOT ENABLE THIS UNLESS YOU TRUST THE TEMPLATE AUTHORS
                                        // look for label elements referring to input elements
                                        // you can then set (replace) the label of the corresponding input
                                        // element by $element->label="my new label text";
        // get text/transalation suppport ------------------------------------------
        //  (flexy compiler only)
        'disableTranslate' => false,    // if true, skips the translation functionality completely
        'locale'        => 'en',        // works with gettext or File_Gettext
        'textdomain'    => '',          // for gettext emulation with File_Gettext
                                        // eg. 'messages' (or you can use the template name.
        'textdomainDir' => '',          // eg. /var/www/site.com/locale
                                        // so the french po file is:
                                        // /var/www/site.com/local/fr/LC_MESSAGE/{textdomain}.po
        
        'Translation2'  => false,       // to make Translation2 a provider.
                                        // rather than gettext.
                                        // set to:
                                        //  'Translation2' => array(
                                        //         'driver' => 'dataobjectsimple',
                                        //         'CommonPageID' => 'page.id'    
                                        //         'options' => array()
                                        //  );
                                        //
                                        // Note: CommonPageID : to use a single page id for all templates
                                        //
                                        // or the slower way.. 
                                        //   = as it requires loading the code..
                                        //
                                        //  'Translation2' => new Translation2('dataobjectsimple','')
                                        
        'DB_DataObject_translator' => false,      // a db dataobject that handles translation (optional)
                                        // the dataobject has to have the method translateFlexyString($flexy, $string);
        
        'charset'       => 'UTF-8',    // charset used with htmlspecialchars to render data.
                                            // experimental
        
        // output options           ------------------------------------------
        'strict'        => false,       // All elements in the template must be defined - 
                                        // makes php E_NOTICE warnings appear when outputing template.
                                        
        'fatalError'    => HTML_TEMPLATE_FLEXY_ERROR_DIE,       // default behavior is to die on errors in template.
        
        'plugins'       => array(),     // load classes to be made available via the plugin method
                                        // eg. = array('Savant') - loads the Savant methods.
                                        // = array('MyClass_Plugins' => 'MyClass/Plugins.php')
                                        //    Class, and where to include it from..
    ); 
    /**
    * The compiled template filename (Full path)
    *
    * @var string
    * @access public
    */
    var $compiledTemplate;
    /**
    * The source template filename (Full path)
    *
    * @var string
    * @access public
    */
    
    
    var $currentTemplate;
    
    /**
    * The getTextStrings Filename
    *
    * @var string
    * @access public
    */
    var $getTextStringsFile;
    /**
    * The serialized elements array file.
    *
    * @var string
    * @access public
    */
    var $elementsFile;
    
     
    /**
    * Array of HTML_elements which is displayed on the template
    * 
    * Technically it's private (eg. only the template uses it..)
    * 
    *
    * @var array of  HTML_Template_Flexy_Elements
    * @access public
    */
    var $elements = array();
    /**
    * The active engine
    * 
    * When outputing elements, we need access to the active engine for translation
    * so before we output, we store the active engine in here.
    * @var array of  HTML_Template_Flexy_Elements
    * @access public
    */
    
    static $activeEngine = false;
    /**
    *   Constructor 
    *
    *   Initializes the Template engine, for each instance, accepts options or
    *   reads from PEAR::getStaticProperty('HTML_Template_Flexy','options');
    *
    *   @access public
    *   @param    array    $options (Optional)
    */
      
    function HTML_Template_Flexy( $options=array() )
    {
        
        $baseoptions = array();
        if (class_exists('PEAR5',false)) {
            $baseoptions = &PEAR5::getStaticProperty('HTML_Template_Flexy','options');
        }
        if (empty($baseoptions) && class_exists('PEAR')) {
            $baseoptions = &PEAR::getStaticProperty('HTML_Template_Flexy','options');
        }
        if ($baseoptions ) {
            foreach( $baseoptions as  $key=>$aOption)  {
                $this->options[$key] = $aOption;
            }
        }
        
        foreach( $options as $key=>$aOption)  {
           $this->options[$key] = $aOption;
        }
        
        $filters = $this->options['filters'];
        if (is_string($filters)) {
            $this->options['filters']= explode(',',$filters);
        }
        
        if (is_string($this->options['templateDir'])) {
            $this->options['templateDir'] = explode(PATH_SEPARATOR,$this->options['templateDir'] );
        }
         
       
    }
    /**
     * given a file, return the possible templates that will becompiled.
     *
     *  @param  string $file  the template to look for.
     *  @return string|PEAR_Error $directory 
     */
   
    function resolvePath ( $file )
    {
        $dirs = array_unique($this->options['templateDir']);
        if ($this->options['templateDirOrder'] == 'reverse') {
            $dirs = array_reverse($dirs);
        }
        $ret = false;
        foreach ($dirs as $tmplDir) {
            if (@!file_exists($tmplDir . DIRECTORY_SEPARATOR .$file)) {
                continue;
            }
            
            if (!$this->options['multiSource'] && ($ret !== false)) {
                  return $this->raiseError("You have more than one template Named {$file} in your paths, found in both".
                        "<BR>{$this->currentTemplate }<BR>{$tmplDir}" . DIRECTORY_SEPARATOR . $file,  
                        HTML_TEMPLATE_FLEXY_ERROR_INVALIDARGS , HTML_TEMPLATE_FLEXY_ERROR_DIE);
            }
            
            $ret = $tmplDir;
            
        }
        return $ret;
        
    }
 
 
    /**
    *   compile the template
    *
    *   @access     public
    *   @version    01/12/03
    *   @author     Wolfram Kriesing <wolfram@kriesing.de>
    *   @param      string  $file   relative to the 'templateDir' which you set when calling the constructor
    *   @return     boolean true on success. (or string, if compileToString) PEAR_Error on failure..
    */
    function compile( $file )
    {
        if (!$file) {
            return $this->raiseError('HTML_Template_Flexy::compile no file selected',
                HTML_TEMPLATE_FLEXY_ERROR_INVALIDARGS,HTML_TEMPLATE_FLEXY_ERROR_DIE);
        }
        
        if (!@$this->options['locale']) {
            $this->options['locale']='en';
        }
        
        
        //Remove the slash if there is one in front, just to be safe.
        $file = ltrim($file,DIRECTORY_SEPARATOR);
        
        
        if (strpos($file,'#')) {
            list($file,$this->options['output.block']) = explode('#', $file);
        }
        
        $parts = array();
        $tmplDirUsed = false;
        
        // PART A mulitlanguage support: ( part B is gettext support in the engine..) 
        //    - user created language version of template.
        //    - compile('abcdef.html') will check for compile('abcdef.en.html') 
        //       (eg. when locale=en)
        
        $this->currentTemplate  = false;
        
        if (preg_match('/(.*)(\.[a-z]+)$/i',$file,$parts)) {
            $newfile = $parts[1].'.'.$this->options['locale'] .$parts[2];
            //var_dump($newfile);
            $match = $this->resolvePath($newfile);
            //var_dump($match);
            if (is_a($match, 'PEAR_Error')) {
                return $match;
            }
            if (false !== $match ) {
                $this->currentTemplate = $match . DIRECTORY_SEPARATOR .$newfile;
                $tmplDirUsed = $match;
            }
            
        }
        print_r($this->currentTemplate);exit;
        // look in all the posible locations for the template directory..
        
        if ($this->currentTemplate  === false) {
            
            
            $match = $this->resolvePath($file);
             if (is_a($match, 'PEAR_Error')) {
                return $match;
            }
            if (false !== $match ) {
                $this->currentTemplate = $match . DIRECTORY_SEPARATOR .$file;
                $tmplDirUsed = $match;
            }
              
        }
        if ($this->currentTemplate === false)  {
            // check if the compile dir has been created
            return $this->raiseError("Could not find Template {$file} in any of the directories<br>" . 
                implode("<BR>",$this->options['templateDir']) ,
                HTML_TEMPLATE_FLEXY_ERROR_INVALIDARGS, HTML_TEMPLATE_FLEXY_ERROR_DIE);
        }
        
        // Savant compatible compiler 
        
        if ( is_string( $this->options['compiler'] ) && ($this->options['compiler'] == 'Raw')) {
            $this->compiledTemplate = $this->currentTemplate;
            $this->debug("Using Raw Compiler");
            return true;
        }
        
        
        // now for the compile target 
        
        //If you are working with mulitple source folders and $options['multiSource'] is set
        //the template folder will be:
        // compiled_tempaltes/{templatedir_basename}_{md5_of_dir}/
        
        
        $compileSuffix = ((count($this->options['templateDir']) > 1) && $this->options['multiSource']) ? 
            DIRECTORY_SEPARATOR  .basename($tmplDirUsed) . '_' .md5($tmplDirUsed) : '';
        
        
        $compileDest = isset($this->options['compileDir']) ? $this->options['compileDir'] : '';
        
        $isTmp = false;
        // Use a default compile directory if one has not been set. 
        if (!$compileDest) {
            // Use session.save_path + 'compiled_templates_' + md5(of sourcedir)
            $compileDest = ini_get('session.save_path') .  DIRECTORY_SEPARATOR . 'flexy_compiled_templates';
            if (!file_exists($compileDest)) {
                require_once 'System.php';
                System::mkdir(array('-p',$compileDest));
            }
            $isTmp = true;
        
        }
        
         
        
        // we generally just keep the directory structure as the application uses it,
        // so we dont get into conflict with names
        // if we have multi sources we do md5 the basedir..
        
       
        $base = $compileDest . $compileSuffix . DIRECTORY_SEPARATOR .$file;
        $fullFile = $this->compiledTemplate    = $base .'.'.$this->options['locale'].'.php';
        $this->getTextStringsFile  = $base .'.gettext.serial';
        $this->elementsFile        = $base .'.elements.serial';
        if (isset($this->options['output.block'])) {
            $this->compiledTemplate    .= '#'.$this->options['output.block'];
        }
        
        if (!empty($this->options['dontCompile'])) {
            return true;
        }
        
        $recompile = false;
        
        $isuptodate = file_exists($this->compiledTemplate)   ?
            (filemtime($this->currentTemplate) == filemtime( $this->compiledTemplate)) : 0;
            
        if( !empty($this->options['forceCompile']) || !$isuptodate ) {
            $recompile = true;
        } 
        // ask the translator if it needs to force a recompile        
        if (!$recompile && ! empty($this->options['DB_DataObject_translator'])) {
        
            static $tr = false;
            if (!$tr) {
                require_once 'DB/DataObject.php';
                $tr = DB_DataObject::factory( $this->options['DB_DataObject_translator']);
            }
            if (method_exists($tr,'translateChanged') ) {
                $recompile = $tr->translateChanged($this);
            }
            
            
        }
        if (!$recompile) {
            $this->debug("File looks like it is uptodate.");
            return true;
        }
        
        
        
        
        if( !@is_dir($compileDest) || !is_writeable($compileDest)) {
            require_once 'System.php';
            
            System::mkdir(array('-p',$compileDest));
        }
        if( !@is_dir($compileDest) || !is_writeable($compileDest)) {
            return $this->raiseError(   "can not write to 'compileDir', which is <b>'$compileDest'</b><br>".
                            "Please give write and enter-rights to it",
                            HTML_TEMPLATE_FLEXY_ERROR_FILE, HTML_TEMPLATE_FLEXY_ERROR_DIE);
        }
        
        if (!file_exists(dirname($this->compiledTemplate))) {
            require_once 'System.php';
            System::mkdir(array('-p','-m', 0770, dirname($this->compiledTemplate)));
        }
         
        // Compile the template in $file. 
        
        require_once 'HTML/Template/Flexy/Compiler.php';
        $this->compiler = HTML_Template_Flexy_Compiler::factory($this->options);
        $ret = $this->compiler->compile($this);
        if (HTML_Template_Flexy_is_a($ret,'PEAR_Error')) {
            return $this->raiseError('HTML_Template_Flexy fatal error:' .$ret->message,
                $ret->code,  HTML_TEMPLATE_FLEXY_ERROR_DIE);
        }
        
        return $ret;
        
        //return $this->$method();
        
    }

     /**
    *  compiles all templates
    *  Used for offline batch compilation (eg. if your server doesn't have write access to the filesystem).
    *
    *   @access     public
    *   @author     Alan Knowles <alan@akbkhome.com>
    *
    */
    function compileAll($dir = '',$regex='/.html$/')
    {
        
        require_once 'HTML/Template/Flexy/Compiler.php';
        $c = new HTML_Template_Flexy_Compiler;
        $c->compileAll($this,$dir,$regex);
    } 
    
    /**
    *   Outputs an object as $t 
    *
    *   for example the using simpletags the object's variable $t->test
    *   would map to {test}
    *
    *   @version    01/12/14
    *   @access     public
    *   @author     Alan Knowles
    *   @param    object   to output  
    *   @param    array  HTML_Template_Flexy_Elements (or any object that implements toHtml())
    *   @return     none
    */
    
    
    function outputObject(&$t,$elements=array()) 
    {
        if (!is_array($elements)) {
            return $this->raiseError(
                'second Argument to HTML_Template_Flexy::outputObject() was an '.gettype($elements) . ', not an array',
                HTML_TEMPLATE_FLEXY_ERROR_INVALIDARGS ,HTML_TEMPLATE_FLEXY_ERROR_DIE);
        }
        if (@$this->options['debug']) {
            echo "output $this->compiledTemplate<BR>";
        }
  
        // this may disappear later it's a Backwards Compatibility fudge to try 
        // and deal with the first stupid design decision to not use a second argument
        // to the method.
       
        if (count($this->elements) && !count($elements)) {
            $elements = $this->elements;
        }
        // end depreciated code
        
        
        $this->elements = $this->getElements();
        
        // Overlay values from $elements to $this->elements (which is created from the template)
        // Remove keys with no corresponding value.
        foreach($elements as $k=>$v) {
            // Remove key-value pair from $this->elements if hasn't a value in $elements.
            if (!$v) {
                unset($this->elements[$k]);
            }
            // Add key-value pair to $this->$elements if it's not there already.
            if (!isset($this->elements[$k])) {
                $this->elements[$k] = $v;
                continue;
            }
            // Call the clever element merger - that understands form values and 
            // how to display them...
            $this->elements[$k] = $this->elements[$k]->merge($v);
        }
        //echo '<PRE>'; print_r(array($elements,$this->elements));
      
        // we use PHP's error handler to hide errors in the template.
        // use $options['strict'] - if you want to force declaration of
        // all variables in the template
        
        
        $_error_reporting = false;
        if (!$this->options['strict']) {
            $_error_reporting = error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);
        }
        if (!is_readable($this->compiledTemplate)) {
              return $this->raiseError( "Could not open the template: <b>'{$this->compiledTemplate}'</b><BR>".
                            "Please check the file permissions on the directory and file ",
                            HTML_TEMPLATE_FLEXY_ERROR_FILE, HTML_TEMPLATE_FLEXY_ERROR_DIE);
        }
        
        // are we using the assign api!
        
        if (isset($this->assign)) {
            if (!$t) {
                $t = (object) $this->assign->variables;
            }
            extract($this->assign->variables);
            foreach(array_keys($this->assign->references) as $_k) {
                $$_k = &$this->assign->references[$_k];
            }
        }
        // used by Flexy Elements etc..
        // note that we are using __ prefix, as this get's exposed to the running template..
        $__old_engine = self::$activeEngine;
        $this->initializeTranslator();
        self::$activeEngine = $this;
        
        //?? not needed?
        $GLOBALS['_HTML_TEMPLATE_FLEXY']['options']  = $this->options;
        
        
        // initialize the translator..
        include($this->compiledTemplate);
        
        self::$activeEngine = $__old_engine;
        
        
        // Return the error handler to its previous state. 
        
        if ($_error_reporting !== false) {
            error_reporting($_error_reporting);
        }
    }
    /**
    *   Outputs an object as $t, buffers the result and returns it.
    *
    *   See outputObject($t) for more details.
    *
    *   @version    01/12/14
    *   @access     public
    *   @author     Alan Knowles
    *   @param      object object to output as $t
    *   @return     string - result
    */
    function bufferedOutputObject($t,$elements=array()) 
    {
        ob_start();
        $this->outputObject($t,$elements);
        $data = ob_get_contents();
        ob_end_clean();
        return $data;
    }
    /**
     * Private - file handler for output to file 
     */
    var $_bufferHandle = false;
    
    /**
    *   Outputs result to a file
    *
    *   See outputObject($t) for more details.
    *
    *   @version    01/12/14
    *   @access     public
    *   @author     Alan Knowles
    *   @param      object object to output as $t
    */
    function outputObjectToFile(&$t,$elements=array(),$filename) 
    {
        $this->_bufferHandle = fopen($filename, 'w');
        
        ob_start(); // outer nesting..
        ob_start( array($this, 'addToBuffer') , 4096, PHP_OUTPUT_HANDLER_STDFLAGS);
        $this->outputObject($t,$elements);
        ob_flush(); // send it???
        ///fwrite($this->_bufferHandle,@);
        @ob_end_clean();
        // not sure why, but this emits errors... when it should not!
        @ob_end_clean();
        
        fclose($this->_bufferHandle);
        $this->_bufferHandle = false;
     }
    /**
     * callback for outputObjectToFile
     *
     */
    function addToBuffer($buffer)
    {
        
        if (!$this->_bufferHandle) {
            return false; 
        }
        fwrite($this->_bufferHandle,$buffer);
        return true;
    }
    
    /**
    * static version which does new, compile and output all in one go.
    *
    *   See outputObject($t) for more details.
    *
    *   @version    01/12/14
    *   @access     public
    *   @author     Alan Knowles
    *   @param      object object to output as $t
    *   @param      filename of template
    *   @return     string - result
    */
    function &staticQuickTemplate($file,&$t) 
    {
        $template = new HTML_Template_Flexy;
        $template->compile($file);
        $template->outputObject($t);
    }
    
    /**
    *   if debugging is on, print the debug info to the screen
    *
    *   @access     public
    *   @author     Alan Knowles <alan@akbkhome.com>
    *   @param      string  $string       output to display
    *   @return     none
    */
    function debug($string) 
    {  
        
        
        if (isset($this) && HTML_Template_Flexy_is_a($this,'HTML_Template_Flexy')) {
            if (!$this->options['debug']) {
                return;
            }
        } else if (empty($GLOBALS['_HTML_TEMPLATE_FLEXY']['debug'])) {
            return;
        }
        
        echo "<PRE><B>FLEXY DEBUG:</B> $string</PRE>";
        
    }
 
   
 
    
    /**
     * @depreciated
     * See element->merge
     *
     * @access   public
     */
     
    function mergeElement($original,$new)
    {
       	    // Clone objects is possible to avoid creating references between elements
         
        // no original - return new
        if (!$original) {
            return clone($new); 
        }
        return $original->merge($new);
        
        
    }  
     
     
    /**
    * Get an array of elements from the template
    *
    * All <form> elements (eg. <input><textarea) etc.) and anything marked as 
    * dynamic  (eg. flexy:dynamic="yes") are converted in to elements
    * (simliar to XML_Tree_Node) 
    * you can use this to build the default $elements array that is used by
    * outputObject() - or just create them and they will be overlayed when you
    * run outputObject()
    *
    *
    * @return   array   of HTML_Template_Flexy_Element sDescription
    * @access   public
    */
    
    function getElements() {
    
        if ($this->elementsFile && file_exists($this->elementsFile)) {
            require_once 'HTML/Template/Flexy/Element.php';
            return unserialize(file_get_contents($this->elementsFile));
        }
        return array();
    }
    
    
    /**
    * Initilalize the translation methods.
    *
    * Loads Translation2 if required.
    * 
     *
    * @return   none 
    * @access   public 
    */
    function initializeTranslator() {
    
        if (is_array($this->options['Translation2'])) {
            require_once 'Translation2.php';
            $this->_options = $this->options; // store the original options..
            $this->options['Translation2'] =  &Translation2::factory(
                $this->options['Translation2']['driver'],
                isset($this->options['Translation2']['options']) ? $this->options['Translation2']['options'] : array(),
                isset($this->options['Translation2']['params']) ? $this->options['Translation2']['params'] : array()
            );
        }
                
        if (is_a($this->options['Translation2'], 'Translation2')) {
            $this->options['Translation2']->setLang($this->options['locale']);
            
            if(empty($this->_options['Translation2']['CommonPageID'])) {
                // fixme - needs to be more specific to which template to use..
    	        foreach ($this->options['templateDir'] as $tt) {
                    $n = basename($this->currentTemplate);
            	    if (substr($this->currentTemplate, 0, strlen($tt)) == $tt) {
                        $n = substr($this->currentTemplate, strlen($tt)+1);
                    }
                    //echo $n;
            	}
            	$this->options['Translation2']->setPageID($n);
            } else {
                $this->options['Translation2']->setPageID($this->options['Translation2']['CommonPageID']);
            }

        } elseif (defined('LC_ALL'))  {
            // not sure what we should really use here... - used to be LC_MESSAGES.. but that did not make sense...
            setlocale(LC_ALL, $this->options['locale']);
        }
        
    }
    
     /**
    * translateString - a gettextWrapper
    *
    * tries to do gettext or falls back on File_Gettext
    * This has !!!NO!!! error handling - if it fails you just get english.. 
    * no questions asked!!!
    * 
    * @param   string       string to translate
    *
    * @return   string      translated string..
    * @access   public
    */
  
    function translateString($string)
    {
        
        if (!empty($this->options['disableTranslate'])) {
            return $string;
        }
        
        // db dataobject easy handling of translations.
        
        if (!empty($this->options['DB_DataObject_translator'])) {
            static $tr = false;
            if (!$tr) {
                require_once 'DB/DataObject.php';
                $tr = DB_DataObject::factory( $this->options['DB_DataObject_translator']);
            }
            $result = $tr->translateFlexyString($this, $string);
            if (!empty($result)) {
                return $result;
            }
            return $string;
        }
        
        
        if (is_a($this->options['Translation2'], 'Translation2')) {
            $result = $this->options['Translation2']->get($string);
            if (!empty($result)) {
                return $result;
            }
            return $string;
        }
        
        // get text will not support english locale translations - use Translation2 to support that
        
        if ( $this->options['locale'] == 'en' ) {
            return $string;
        }
        // note this stuff may have been broken by removing the \n replacement code 
        // since i dont have a test for it... it may remain broken..
        // use Translation2 - it has gettext backend support
        // and should sort out the mess that \n etc. entail.
        
        
        $prefix = basename($GLOBALS['_HTML_TEMPLATE_FLEXY']['filename']).':';
        if (!empty($this->options['debug'])) {
            echo __CLASS__.":TRANSLATING $string<BR>\n";
        }
        
        if (function_exists('gettext') && !$this->options['textdomain']) {
            if (@$this->options['debug']) {
                echo __CLASS__.":USING GETTEXT?<BR>";
            }
            $t = gettext($string);
            
            if ($t != $string) {
                return $t;
            }
            $tt = gettext($prefix.$string);
            if (!empty($tt) && $tt != $prefix.$string) {
                return $tt;
            }
                // give up it's not translated anywhere...
            return $string;
             
        }
        if (!$this->options['textdomain'] || !$this->options['textdomainDir']) {
            // text domain is not set..
            if (!empty($this->options['debug'])) {
                echo __CLASS__.":MISSING textdomain settings<BR>";
            }
            return $string;
        }
        $pofile = $this->options['textdomainDir'] . 
                '/' . $this->options['locale'] . 
                '/LC_MESSAGES/' . $this->options['textdomain'] . '.po';
        
        
        // did we try to load it already..
        if (isset($GLOBALS['_'.__CLASS__]['PO'][$pofile]) && $GLOBALS['_'.__CLASS__]['PO'][$pofile] === false) {
            if (@$this->options['debug']) {
                echo __CLASS__.":LOAD failed (Cached):<BR>";
            }
            return $string;
        }
        if (empty($GLOBALS['_'.__CLASS__]['PO'][$pofile])) {
            // default - cant load it..
            $GLOBALS['_'.__CLASS__]['PO'][$pofile] = false;
            if (!file_exists($pofile)) {
                 if (!empty($this->options['debug'])) {
                    echo __CLASS__.":LOAD failed: {$pofile}<BR>";
                }   
                return $string;
            }
            
            if (!@include_once 'File/Gettext.php') {
                if (!empty($this->options['debug'])) {
                    echo __CLASS__.":LOAD no File_gettext:<BR>";
                }
                return $string;
            }
            
            $GLOBALS['_'.__CLASS__]['PO'][$pofile] = File_Gettext::factory('PO', $pofile);
            $GLOBALS['_'.__CLASS__]['PO'][$pofile]->load();
            //echo '<PRE>'.htmlspecialchars(print_r($GLOBALS['_'.__CLASS__]['PO'][$pofile]->strings,true));
            
        }
        $po = $GLOBALS['_'.__CLASS__]['PO'][$pofile];
        // we should have it loaded now...
        // this is odd - data is a bit messed up with CR's
        $string = str_replace('\n', "\n", $string);
        
        if (isset($po->strings[$prefix.$string])) {
            return $po->strings[$prefix.$string];
        }
        
        if (!isset($po->strings[$string])) {
            if (!empty($this->options['debug'])) {
                    echo __CLASS__.":no match:<BR>";
            }
            return $string;
        }
        if (!empty($this->options['debug'])) {
            echo __CLASS__.":MATCHED: {$po->strings[$string]}<BR>";
        }
        
        // finally we have a match!!!
        return $po->strings[$string];
        
    } 
    
    /**
    * Lazy loading of PEAR, and the error handler..
    * This should load HTML_Template_Flexy_Error really..
    * 
    * @param   string message
    * @param   int      error type.
    * @param   int      an equivalant to pear error return|die etc.
    *
    * @return   object      pear error.
    * @access   public
    */
  
    
    function raiseError($message, $type = null, $fatal = HTML_TEMPLATE_FLEXY_ERROR_RETURN ) 
    {
        HTML_Template_Flexy::debug("<B>HTML_Template_Flexy::raiseError</B>$message");
        require_once 'PEAR.php';
        $p = new PEAR();
        if (HTML_Template_Flexy_is_a($this,'HTML_Template_Flexy') &&  ($fatal == HTML_TEMPLATE_FLEXY_ERROR_DIE)) {
            // rewrite DIE!
            return $p->raiseError($message, $type, $this->options['fatalError']);
        }
        if (isset($GLOBALS['_HTML_TEMPLATE_FLEXY']['fatalError']) &&  ($fatal == HTML_TEMPLATE_FLEXY_ERROR_DIE)) {
            return $p->raiseError($message, $type,$GLOBALS['_HTML_TEMPLATE_FLEXY']['fatalError']);
        }
        return $p->raiseError($message, $type, $fatal);
    }
    /**
    * static version of raiseError 
    * @see HTML_Template_Flexy::raiseError
    * 
    * @param   string message
    * @param   int      error type.
    * @param   int      an equivalant to pear error return|die etc.
    *
    * @return   object      pear error.
    * @static
    * @access   public
    */
    static function staticRaiseError($message, $type = null, $fatal = HTML_TEMPLATE_FLEXY_ERROR_RETURN ) 
    {
        HTML_Template_Flexy::debug("<B>HTML_Template_Flexy::raiseError</B>$message");
        require_once 'PEAR.php';
        if (isset($GLOBALS['_HTML_TEMPLATE_FLEXY']['fatalError']) &&  ($fatal == HTML_TEMPLATE_FLEXY_ERROR_DIE)) {
            
            $p = new PEAR();
            return $p->raiseError($message, $type,$GLOBALS['_HTML_TEMPLATE_FLEXY']['fatalError']);
        }
        $p = new PEAR();
        return $p->raiseError($message, $type, $fatal);
    }

    /**
    * 
    * Assign API - 
    * 
    * read the docs on HTML_Template_Flexy_Assign::assign()
    *
    * @param   varargs ....
    * 
    *
    * @return   mixed    PEAR_Error or true?
    * @access   public
    * @see  HTML_Template_Flexy_Assign::assign()
    * @status alpha
    */
  
    function setData() {
        require_once 'HTML/Template/Flexy/Assign.php';
        // load assigner..
        if (!isset($this->assign)) {
            $this->assign = new HTML_Template_Flexy_Assign;
        }
        return $this->assign->assign(func_get_args());
    }
    /**
    * 
    * Assign API - by Reference
    * 
    * read the docs on HTML_Template_Flexy_Assign::assign()
    *
    * @param  key  string
    * @param  value mixed
    * 
    * @return   mixed    PEAR_Error or true?
    * @access   public
    * @see  HTML_Template_Flexy_Assign::assign()
    * @status alpha
    */
        
    function setDataByRef($k,&$v) {
        require_once 'HTML/Template/Flexy/Assign.php';
        // load assigner..
        if (!isset($this->assign)) {
            $this->assign = new HTML_Template_Flexy_Assign;
        }
        $this->assign->assignRef($k,$v);
    } 
    /**
    * 
    * Plugin (used by templates as $this->plugin(...) or {this.plugin(#...#,#....#)}
    * 
    * read the docs on HTML_Template_Flexy_Plugin()
    *
    * @param  varargs ....
    * 
    * @return   mixed    PEAR_Error or true?
    * @access   public
    * @see  HTML_Template_Flexy_Plugin
    * @status alpha
    */
    function plugin() {
        require_once 'HTML/Template/Flexy/Plugin.php';
        // load pluginManager.
        if (!isset($this->plugin)) {
            $this->plugin = new HTML_Template_Flexy_Plugin;
            $this->plugin->flexy = &$this;
        }
        return $this->plugin->call(func_get_args());
    } 
    /**
    * 
    * output / display ? - outputs an object, without copy by references..
    * 
    * @param  optional mixed object to output
    * 
    * @return   mixed    PEAR_Error or true?
    * @access   public
    * @see  HTML_Template_Flexy::ouptutObject
    * @status alpha
    */
    function output($object = false) 
    {
        return $this->outputObject($object);
    }
    
    /**
    * 
    * render the template with data..
    * 
    * @param  optional mixed object to output
    * 
    * @return   mixed    PEAR_Error or true?
    * @access   public
    * @see  HTML_Template_Flexy::ouptutObject
    * @status alpha
    */
    function toString($object = false) 
    {
        return $this->bufferedOutputObject($object);
    }
    
}
