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
// | Authors: Alan Knowles <alan@akbkhome.com>                            |
// +----------------------------------------------------------------------+
//
// $Id: Flexy.php 315533 2011-08-26 02:39:02Z alan_k $
//
//  Base Compiler Class
//  Standard 'Original Flavour' Flexy compiler

// this does the main conversion, (eg. for {vars and methods}) 
// it relays into Compiler/Tag & Compiler/Flexy for tags and namespace handling.




require_once 'HTML/Template/Flexy/Tokenizer.php';
require_once 'HTML/Template/Flexy/Token.php';
 
class HTML_Template_Flexy_Compiler_Flexy extends HTML_Template_Flexy_Compiler {
    
    /**
     * reference to calling controller
     *
     * @var $flexy HTML_Template_Flexy
     * @access public
     */
    var $flexy;
    
    
        
    /**
    * The current template (Full path)
    *
    * @var string
    * @access public
    */
    var $currentTemplate;
    
    /**
     * when using flexy::contents - this contains the map of
     * <... flexy:conntents="KEY">.....VALUE ...<...>
     * 
     */
    var $contentStrings = array();
    
    /**
    * The compile method.
    * 
    * @params   object HTML_Template_Flexy
    * @params   string|false string to compile of false to use a file.
    * @return   string   filename of template
    * @access   public
    */
    function compile($flexy, $string=false) 
    {
        // read the entire file into one variable
        
        // note this should be moved to new HTML_Template_Flexy_Token
        // and that can then manage all the tokens in one place..
        global $_HTML_TEMPLATE_FLEXY_COMPILER;
        
        $this->flexy = $flexy;
        
        $this->currentTemplate  = $flexy->currentTemplate;
        
        $gettextStrings = &$_HTML_TEMPLATE_FLEXY_COMPILER['gettextStrings'];
        $gettextStrings = array(); // reset it.
        
        if (@$this->options['debug']) {
            echo "compiling template $flexy->currentTemplate<BR>";
            
        }
         
        // reset the elements.
        $flexy->_elements = array();
        
        // replace this with a singleton??
        
        $GLOBALS['_HTML_TEMPLATE_FLEXY']['currentOptions']  = $this->options;
        $GLOBALS['_HTML_TEMPLATE_FLEXY']['elements']        = array();
        $GLOBALS['_HTML_TEMPLATE_FLEXY']['filename']        = $flexy->currentTemplate;
        $GLOBALS['_HTML_TEMPLATE_FLEXY']['prefixOutput']    = '';
        $GLOBALS['_HTML_TEMPLATE_FLEXY']['compiledTemplate']= $flexy->compiledTemplate;
        
        
        // initialize Translation 2, and 
        $this->flexy->initializeTranslator();
        
        
        // load the template!
        $data = $string;
        $res = false;
        if ($string === false) {
            $data = file_get_contents($flexy->currentTemplate);
        }
         
        // PRE PROCESS {_(.....)} translation markers.
        if (strpos($data, '{_(') !== false) {
            $data = $this->preProcessTranslation($data);
        }
        
        // Tree generation!!!
        
        
        
        if (!$this->options['forceCompile'] && isset($_HTML_TEMPLATE_FLEXY_COMPILER['cache'][md5($data)])) {
            $res = $_HTML_TEMPLATE_FLEXY_COMPILER['cache'][md5($data)];
        } else {
        
             
            $tokenizer = new HTML_Template_Flexy_Tokenizer($data);
            $tokenizer->fileName = $flexy->currentTemplate;
            
            
              
            //$tokenizer->debug=1;
            $tokenizer->options['ignore_html'] = $this->options['nonHTML'];
            
          
            require_once 'HTML/Template/Flexy/Token.php';
            $res = HTML_Template_Flexy_Token::buildTokens($tokenizer);
            
            
            if ($this->is_a($res, 'PEAR_Error')) {
                return $res;
            }       
            $_HTML_TEMPLATE_FLEXY_COMPILER['cache'][md5($data)] = $res;
            
        }
        
        
        // technically we shouldnt get here as we dont cache errors..
        if ($this->is_a($res, 'PEAR_Error')) {
            return $res;
        }
        
        // turn tokens into Template..
        
        //var_dump($this);exit;
        $data = $res->compile($this);
        
        if ($this->is_a($data, 'PEAR_Error')) {
            return $data;
        }
        
        $data = $GLOBALS['_HTML_TEMPLATE_FLEXY']['prefixOutput'] . $data;
        
        if (   $flexy->options['debug'] > 1) {
            echo "<B>Result: </B><PRE>".htmlspecialchars($data)."</PRE><BR>\n";
        }

        if ($this->options['nonHTML']) {
           $data =  str_replace("?>\n", "?>\n\n", $data);
        }
        
         
        
        
        // at this point we are into writing stuff...
        if ($flexy->options['compileToString']) {
            if (   $flexy->options['debug']) {
                echo "<B>Returning string:<BR>\n";
            }

            $flexy->elements =  $GLOBALS['_HTML_TEMPLATE_FLEXY']['elements'];
            return $data;
        }
        
        
        
        
        // error checking?
        $file  = $flexy->compiledTemplate;
        if (isset($flexy->options['output.block'])) {
            list($file, $part) = explode('#', $file);
        }
         if( ($cfp = fopen($file, 'w')) ) {
            if ($flexy->options['debug']) {
                echo "<B>Writing: </B>$file<BR>\n";
            }
            fwrite($cfp, $data);
            fclose($cfp);
            
            chmod($file, 0775);
            // make the timestamp of the two items match.
            clearstatcache();
            touch($file, filemtime($flexy->currentTemplate));
            
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($file);
            }
            // why?? -- see output.block
            
            if ($file != $flexy->compiledTemplate) {
                chmod($flexy->compiledTemplate, 0775);
                // make the timestamp of the two items match.
                clearstatcache();
                $mtime = filemtime($flexy->currentTemplate);
                if (!empty($flexy->options['DB_DataObject_translator'])) {
                    require_once 'DB/DataObject.php';
                    $tr = DB_DataObject::factory( $flexy->options['DB_DataObject_translator']);
                    if (method_exists($tr,'lastUpdated') ) {
                        $last_updated = $tr->lastUpdated($flexy);
                        $mtime = $last_updated !== false ? max(strtotime($last_updated), $mtime) : $mtime;   
                    }
                    
                }
                var_dump($mtime);
                touch($flexy->compiledTemplate, $mtime);
                 
                if (function_exists('opcache_invalidate')) {
                    opcache_invalidate($flexy->compiledTemplate);
                }
            }
             
            
        } else {
            return HTML_Template_Flexy::staticRaiseError('HTML_Template_Flexy::failed to write to '.$flexy->compiledTemplate,
                HTML_TEMPLATE_FLEXY_ERROR_FILE, HTML_TEMPLATE_FLEXY_ERROR_RETURN);
        }
        // gettext strings
         
        // sometimes this happen's at the same time, and we get errors displayed...
        if (file_exists($flexy->getTextStringsFile)) {
            @unlink($flexy->getTextStringsFile);
        }

        if($gettextStrings && ($cfp = fopen( $flexy->getTextStringsFile, 'w') ) ) {
            
            fwrite($cfp, serialize(array_unique($gettextStrings)));
            fclose($cfp);
            chmod($flexy->getTextStringsFile, 0664);
        }
        
        // elements
        if (file_exists($flexy->elementsFile)) {
            unlink($flexy->elementsFile);
        }
        
        if( $GLOBALS['_HTML_TEMPLATE_FLEXY']['elements'] &&
            ($cfp = fopen( $flexy->elementsFile, 'w') ) ) {
            fwrite($cfp, serialize( $GLOBALS['_HTML_TEMPLATE_FLEXY']['elements']));
            fclose($cfp);
            chmod($flexy->elementsFile, 0664);
            // now clear it.
        
        }
        
        return true;
    }
    
    
    
    
    
    
    /**
    * do the early tranlsation of {_(......)_} text
    *
    * 
    * @param    input string
    * @return   output string
    * @access   public 
    */
    function preProcessTranslation($data) {
        global $_HTML_TEMPLATE_FLEXY_COMPILER;
        $matches = array();
        $lmatches = explode ('{_(', $data);
        array_shift($lmatches);
        // shift the first..
        foreach ($lmatches as $k) {
            if (false === strpos($k, ')_}')) {
                continue;
            }
            $x = explode(')_}', $k);
            $matches[] = $x[0];
        }
    
    
       //echo '<PRE>';print_r($matches);
        // we may need to do some house cleaning here...
        $_HTML_TEMPLATE_FLEXY_COMPILER['gettextStrings'] = $matches;
        
        
        // replace them now..  
        // ** leaving in the tag (which should be ignored by the parser..
        // we then get rid of the tags during the toString method in this class.
        foreach($matches as $v) {
            $data = str_replace('{_('.$v.')_}', '{_('.$this->flexy->translateString($v).')_}', $data);
        }
        return $data;
    }    

    
    
    
    
    /**
    * Flag indicating compiler is inside {_( .... )_} block, and should not
    * add to the gettextstrings array.
    *
    * @var boolean 
    * @access public
    */
    var $inGetTextBlock = false;
    
    /**
    * This is the base toString Method, it relays into toString{TokenName}
    *
    * @param    object    HTML_Template_Flexy_Token_*
    * 
    * @return   string     string to build a template
    * @access   public 
    * @see      toString*
    */
  

    function toString($element) 
    {
         static $len = 26; // strlen('HTML_Template_Flexy_Token_');
        if ($this->options['debug'] > 1) {
            $x = $element;
            //unset($x->children);
            //echo htmlspecialchars(print_r($x,true))."<BR>\n";
        }
        if ($element->token == 'GetTextStart') {
            $this->inGetTextBlock = true;
            return '';
        }
        if ($element->token == 'GetTextEnd') {
            $this->inGetTextBlock = false;
            return '';
        }
        
            
        $class = get_class($element);
        if (strlen($class) >= $len) {
            $type = substr($class, $len);
            return $this->{'toString'.$type}($element);
        }
        
        $ret = $element->value;
        $add = $element->compileChildren($this);
        if ($this->is_a($add, 'PEAR_Error')) {
            return $add;
        }
        $ret .= $add;
        
        if ($element->close) {
            $add = $element->close->compile($this);
            if ($this->is_a($add, 'PEAR_Error')) {
                return $add;
            }
            $ret .= $add;
        }
        
        return $ret;
    }


    /**
    *   HTML_Template_Flexy_Token_Else toString 
    *
    * @param    object    HTML_Template_Flexy_Token_Else
    * 
    * @return   string     string to build a template
    * @access   public 
    * @see      toString*
    */
  

    function toStringElse($element) 
     {
        // pushpull states to make sure we are in an area.. - should really check to see 
        // if the state it is pulling is a if...
        if ($element->pullState() === false) {
            return $this->appendHTML(
                "<font color=\"red\">Unmatched {else:} on line: {$element->line}</font>"
                );
        }
        $element->pushState();
        return $this->appendPhp("} else {");
    }
    
    /**
    *   HTML_Template_Flexy_Token_End toString 
    *
    * @param    object    HTML_Template_Flexy_Token_Else
    * 
    * @return   string     string to build a template
    * @access   public 
    * @see      toString*
    */
  
    function toStringEnd($element) 
    {
        // pushpull states to make sure we are in an area.. - should really check to see 
        // if the state it is pulling is a if...
        if ($element->pullState() === false) {
            return $this->appendHTML(
                "<font color=\"red\">Unmatched {end:} on line: {$element->line}</font>"
                );
        }
         
        return $this->appendPhp("}");
    }

    /**
    *   HTML_Template_Flexy_Token_EndTag toString 
    *
    * @param    object    HTML_Template_Flexy_Token_EndTag
    * 
    * @return   string     string to build a template
    * @access   public 
    * @see      toString*
    */
  


    function toStringEndTag($element) 
    {
        return $this->toStringTag($element);
    }
        
    
    
    /**
    *   HTML_Template_Flexy_Token_Foreach toString 
    *
    * @param    object    HTML_Template_Flexy_Token_Foreach 
    * 
    * @return   string     string to build a template
    * @access   public 
    * @see      toString*
    */
  
    
    function toStringForeach($element) 
    {
    
        $loopon = $element->toVar($element->loopOn);
        if ($this->is_a($loopon, 'PEAR_Error')) {
            return $loopon;
        }
        
        $ret = 'if ($this->options[\'strict\'] || ('.
            'is_array('. $loopon. ')  || ' .
            'is_object(' . $loopon  . '))) ' .
            'foreach(' . $loopon  . " ";
            
        $ret .= "as \${$element->key}";
        
        if ($element->value) {
            $ret .=  " => \${$element->value}";
        }
        $ret .= ") {";
        
        $element->pushState();
        $element->pushVar($element->key);
        $element->pushVar($element->value);
        return $this->appendPhp($ret);
    }
    /**
    *   HTML_Template_Flexy_Token_If toString 
    *
    * @param    object    HTML_Template_Flexy_Token_If
    * 
    * @return   string     string to build a template
    * @access   public 
    * @see      toString*
    */
  
    function toStringIf($element) 
    {
        
        $var = $element->toVar($element->condition);
        if ($this->is_a($var, 'PEAR_Error')) {
            return $var;
        }
        
        $ret = "if (".$element->isNegative . $var .")  {";
        $element->pushState();
        return $this->appendPhp($ret);
    }

   /**
    *  get Modifier Wrapper 
    *
    * converts :h, :u, :r , .....
    * @param    object    HTML_Template_Flexy_Token_Method|Var
    * 
    * @return   array prefix,suffix
    * @access   public 
    * @see      toString*
    */

    function getModifierWrapper($element) 
    {
        $prefix = 'echo ';
        
        $suffix = '';
        $modifier = strlen(trim($element->modifier)) ? $element->modifier : ' ';
        
        switch ($modifier) {
            case 'h':
                break;
            case 'u':
                $prefix = 'echo urlencode(';
                $suffix = ')';
                break;
            case 'r':
                $prefix = 'echo \'<pre>\'; echo htmlspecialchars(print_r(';
                $suffix = ',true)); echo \'</pre>\';';
                break;                
            case 'n': 
                // blank or value..
                $numberformat = @$GLOBALS['_HTML_TEMPLATE_FLEXY']['currentOptions']['numberFormat'];
                $prefix = 'echo number_format(';
                $suffix = $GLOBALS['_HTML_TEMPLATE_FLEXY']['currentOptions']['numberFormat'] . ')';
                break;
            case 'b': // nl2br + htmlspecialchars
                $prefix = 'echo nl2br(htmlspecialchars(';
                
                // add language ?
                $suffix = '))';
                break;
            case 'e':
                $prefix = 'echo htmlentities(';
                // add language ?
                $suffix = ')';
                break;
             
            case ' ':
                $prefix = 'echo htmlspecialchars(';
                // add language ?
                $suffix = ',ENT_IGNORE)';
                break;
            default:
               $prefix = 'echo $this->plugin("'.trim($element->modifier) .'",';
               $suffix = ')'; 
            
            
        }
        
        return array($prefix, $suffix);
    }



  /**
    *   HTML_Template_Flexy_Token_Var toString 
    *
    * @param    object    HTML_Template_Flexy_Token_Method
    * 
    * @return   string     string to build a template
    * @access   public 
    * @see      toString*
    */
  
    function toStringVar($element) 
    {
        // ignore modifier at present!!
        
        $var = $element->toVar($element->value);
        if ($this->is_a($var, 'PEAR_Error')) {
            return $var;
        }
        list($prefix, $suffix) = $this->getModifierWrapper($element);
        return $this->appendPhp( $prefix . $var . $suffix .';');
    }
   /**
    *   HTML_Template_Flexy_Token_Method toString 
    *
    * @param    object    HTML_Template_Flexy_Token_Method
    * 
    * @return   string     string to build a template
    * @access   public 
    * @see      toString*
    */
  
    function toStringMethod($element) 
    {

              
        // set up the modifier at present!!
         
        list($prefix, $suffix) = $this->getModifierWrapper($element);
        
        // add the '!' to if
        
        if ($element->isConditional) {
            $prefix = 'if ('.$element->isNegative;
            $element->pushState();
            $suffix = ')';
        }  
        
        
        // check that method exists..
        // if (method_exists($object,'method');
        $bits = explode('.', $element->method);
        $method = array_pop($bits);
        
        $object = implode('.', $bits);
        
        $var = $element->toVar($object);
        if ($this->is_a($var, 'PEAR_Error')) {
            return $var;
        }
        
        if (($object == 'GLOBALS') && 
            $GLOBALS['_HTML_TEMPLATE_FLEXY']['currentOptions']['globalfunctions']) {
            // we should check if they something weird like: GLOBALS.xxxx[sdf](....)
            $var = $method;
        } else {
            $prefix = 'if ($this->options[\'strict\'] || (isset('.$var.
                ') && method_exists('.$var .", '{$method}'))) " . $prefix;
            $var = $element->toVar($element->method);
        }
        

        if ($this->is_a($var, 'PEAR_Error')) {
            return $var;
        }
        
        $ret  =  $prefix;
        $ret .=  $var . "(";
        $s =0;
         
       
         
        foreach($element->args as $a) {
             
            if ($s) {
                $ret .= ",";
            }
            $s =1;
            if ($a[0] == '#') {
                if (is_numeric(substr($a, 1, -1))) {
                    $ret .= substr($a, 1, -1);
                } else {
                    $ret .= '"'. addslashes(substr($a, 1, -1)) . '"';
                }
                continue;
            }
            
            $var = $element->toVar($a);
            if ($this->is_a($var, 'PEAR_Error')) {
                return $var;
            }
            $ret .= $var;
            
        }
        $ret .= ")" . $suffix;
        
        if ($element->isConditional) {
            $ret .= ' { ';
        } else {
            $ret .= ";";
        }
        
        
        
        return $this->appendPhp($ret); 
        
         

   }
   /**
    *   HTML_Template_Flexy_Token_Processing toString 
    *
    * @param    object    HTML_Template_Flexy_Token_Processing 
    * 
    * @return   string     string to build a template
    * @access   public 
    * @see      toString*
    */


    function toStringProcessing($element) 
    {
        // if it's XML then quote it..
        if (strtoupper(substr($element->value, 2, 3)) == 'XML') { 
            return $this->appendPhp("echo '" . str_replace("'", "\\"."'", $element->value) . "';");
        }
        // otherwise it's PHP code - so echo it..
        return $element->value;
    }
    
    /**
    *   HTML_Template_Flexy_Token_Text toString 
    *
    * @param    object    HTML_Template_Flexy_Token_Text
    * 
    * @return   string     string to build a template
    * @access   public 
    * @see      toString*
    */



    function toStringText($element) 
    {
        
        // first get rid of stuff thats not translated etc.
        // empty strings => output.
        // comments -> just output
        // our special tags -> output..
        
        if (!strlen(trim($element->value) )) {
            return $this->appendHtml($element->value);
        }
        // dont add comments to translation lists.
         
        if (substr($element->value, 0, 4) == '<!--') {
            return $this->appendHtml($element->value);
        }
        // ignore anything wrapped with {_( .... )_}
        if ($this->inGetTextBlock) {
            return $this->appendHtml($element->value);
        }
        
        
        if (!$element->isWord()) {
            return $this->appendHtml($element->value);
        }
        
        // grab the white space at start and end (and keep it!
        
        $value = ltrim($element->value);
        $front = substr($element->value, 0, -strlen($value));
        $value = rtrim($element->value);
        $rear  = substr($element->value, strlen($value));
        $value = trim($element->value);
        
        
        // convert to escaped chars.. (limited..)
        //$value = strtr($value,$cleanArray);
        
        // this only applies to html templates
        if (empty($this->flexy->options['nonHTML'])) {
            $this->addStringToGettext($value);
            $value = $this->flexy->translateString($value);
        }
        // its a simple word!
        return $this->appendHtml($front . $value . $rear);
        
    }
    
    
    
      /**
    *   HTML_Template_Flexy_Token_Cdata toString 
    *
    * @param    object    HTML_Template_Flexy_Token_Cdata ?
    * 
    * @return   string     string to build a template
    * @access   public 
    * @see      toString*
    */



    function toStringCdata($element) 
    {
        return $this->appendHtml($element->value);
    }
    
    
    
    
    
    
    
    
    
    
    /**
    * addStringToGettext 
    *
    * Adds a string to the gettext array. 
    * 
    * @param   mixed        preferably.. string to store
    *
    * @return   none
    * @access   public
    */
    
    function addStringToGettext($string) 
    {
        if (!empty($this->options['disableTranslate'])) {
            return;
        }
        if (!is_string($string)) {
            return;
        }
        
        if (!preg_match('/\w+/i', $string)) {
            return;
        }
        $string = trim($string);
        
        if (substr($string, 0, 4) == '<!--') {
            return;
        }
        
        $GLOBALS['_HTML_TEMPLATE_FLEXY_COMPILER']['gettextStrings'][] = $string;
    }
    
    
    
     /**
    *   HTML_Template_Flexy_Token_Tag toString 
    *
    * @param    object    HTML_Template_Flexy_Token_Tag
    * 
    * @return   string     string to build a template
    * @access   public 
    * @see      toString*
    */
  
    function toStringTag($element) {
        
        $original = $element->getAttribute('ALT');
        // techncially only input type=(submit|button|input) alt=.. applies, but we may 
        // as well translate any occurance...
        if ( (($element->tag == 'IMG') || ($element->tag == 'INPUT'))
                && is_string($original) && strlen($original)) {
            $this->addStringToGettext($original);
            $quote = $element->ucAttributes['ALT'][0];
            $element->ucAttributes['ALT'] = $quote  . $this->flexy->translateString($original). $quote;
        }
        $original = $element->getAttribute('TITLE');
        if (is_string($original) && strlen($original)) {
            $this->addStringToGettext($original);
            $quote = $element->ucAttributes['TITLE'][0];
            $element->ucAttributes['TITLE'] = $quote  . $this->flexy->translateString($original). $quote;
        }
         
        
        if (strpos($element->tag, ':') === false) {
            $namespace = 'Tag';
        } else {
            $bits =  explode(':', $element->tag);
            $namespace = $bits[0];
        }
        if ($namespace[0] == '/') {
            $namespace = substr($namespace, 1);
        }
        if (empty($this->tagHandlers[$namespace])) {
            
            require_once 'HTML/Template/Flexy/Compiler/Flexy/Tag.php';
            $this->tagHandlers[$namespace] = HTML_Template_Flexy_Compiler_Flexy_Tag::factory($namespace, $this);
            if (!$this->tagHandlers[$namespace] ) {
                return HTML_Template_Flexy::staticRaiseError('HTML_Template_Flexy::failed to create Namespace Handler '.$namespace . 
                    ' in file ' . $GLOBALS['_HTML_TEMPLATE_FLEXY']['filename'],
                    HTML_TEMPLATE_FLEXY_ERROR_SYNTAX, HTML_TEMPLATE_FLEXY_ERROR_RETURN);
            }
                
        }
        return $this->tagHandlers[$namespace]->toString($element);
        
        
    }
     /**
     * PHP5 compat - arg...
     * - where else does this affect
     */
    function classExists($class)
    {
        return (substr(phpversion(),0,1) < 5) ? class_exists($class) :  class_exists($class,false);
    }


}
