<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2015 The PHP Group                                |
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
// +----------------------------------------------------------------------+
//


/**
* The Standard Tag filter
*
* @abstract
* does all the clever stuff...
*
* Security Notes:
*   Templates should not originate from untrusted sources,
*    - the  method(#.....#) could be regarded as insecure.
*    - there is no attempt to protect your from <script / <?php in templates.
*
* @package    HTML_Template_Flexy
*
*/


class HTML_Template_Flexy_Compiler_Regex_SimpleTags
{
    /*
    *   @var     object HTML_Template_Flexy   the main engine
    */
    var $engine; // the engine (with options)
    /*
    *   @var    string   $start    the start tag for the template (escaped for regex)
    */
    var $start = '\{';

     /*
    *   @var    string   $stop    the stopt tag for the template (escaped for regex)
    */
    var $stop = '\}';
     /*
    *   @var    string   $error    show/hide the PHP error messages on/off in templates
    */
    var $error = "@"; // change to blank to debug errors.
    /**
    * Standard Set Engine
    *
    *
    * @param   object HTML_Template_Flexy   the main engine
    * @access   private
    */
    function _set_engine(&$engine) {
        $this->engine = &$engine;
        if ($this->engine->options['debug']) {
            $this->error = "";
        }
    }



    /**
    * Standard Variable replacement
    *
    *
    * Maps variables
    * {i.xyz}             maps to  <?php echo htmlspecialchars($i->xyz)?>
    * {i.xyz:h}           maps to  <?php echo $i->xyz?>
    * {i.xyz:u}           maps to  <?php echo urlencode($i->xyz)?>
    * {i.xyz:ru}           maps to  <?php echo rawurlencode($i->xyz)?>
    *
    * {i.xyz:r}           maps to  <PRE><?php echo print_r($i->xyz)?></PRE>
    * {i.xyz:n}           maps to  <?php echo nl2br(htmlspecialchars($i->xyz))?>
    *
    *
    * @param   string    $input the template
    * @return    string   the result of the filtering
    * @access   public
    */



    function variables ($input)
    {
        $input = preg_replace_callback(
            "/".$this->start."([a-z0-9_.]+)(:({$this->modifiers}))?".$this->stop."/i",
            function($m) {
                return $this->_modifiers(
                    $this->error . '$' . str_replace('.','->',$m[1]) ,
                    empty($m[3]) ? '' : $m[3]
                );
            },
            
            $input);

 
        return $input;

    }
     /**
    * Urlencoded Variable replacement
    *
    * Often when you use a WYSISYG editor, it replaces { in
    * the  href="{somevar}" with the urlencoded version, this bit fixes it.
    *
    * Maps variables
    * %??i.xyz%??             maps to  <?php echo htmlspecialchars($i->xyz)?>
    * %??i.xyz:h%??           maps to  <?php echo $i->xyz?>
    * %??i.xyz:u%??           maps to  <?php echo urlencode($i->xyz)?>
    * %??i.xyz:ru%??           maps to  <?php echo urlencode($i->xyz)?>
    *           THIS IS PROBABLY THE ONE TO USE!
    *
    * %??i.xyz:uu%??           maps to <?php echo urlencode(urlencode($i->xyz))?> WHY?????
    *
    *
    * @param   string    $input the template
    * @return    string   the result of the filtering
    * @access   public
    */

    function urlencoded_variables ($input) {
        
        $input = preg_replace_callback(
            "/".urlencode(stripslashes($this->start))."([a-z0-9_.]+)(:({$this->modifiers}))?".
                     urlencode(stripslashes($this->stop))."/i",
         
            function($m) {
                return $this->_modifiers(
                    $this->error . '$' . str_replace('.','->',$m[1]) ,
                    empty($m[3]) ? '' : $m[3]
                );
            },
             
            $input);

 
         
        return $input;
    }
     /**
    * Calling Methods
    *
    * This allows you to call methods of your application
    *
    * Maps Methods
    * {t.xxxx_xxxx()}                 maps to <?php echo htmlspecialchars($t->xxxx_xxxx())?>
    * {t.xxxx_xxxx():h}               maps to <?php echo $t->xxxx_xxxx()?>
    *
    * {t.xxxx_xxxx(sssss.dddd)}       maps to <?php echo htmlspecialchars($t->xxxx_xxxx($ssss->dddd))?>
    * {t.xxxx_xxxx(sssss.dddd):h}     maps to <?php echo $t->xxxx_xxxx($ssss->dddd)?>
    * {t.xxxx_xxxx(sssss.dddd):s}     maps to <?php highlight_string($t->xxxx_xxxx($ssss->dddd))?>
    *
    * {t.xxxx_xxxx(#XXXXX#)}          maps to <?php echo htmlspecialchars($t->xxxx_xxxx('XXXXXX'))?>
    * {t.xxxx_xxxx(#XXXXX#):h}        maps to <?php echo $t->xxxx_xxxx('XXXXXX')?>
    *
    * {t.xxxx_xxxx(sss.ddd,sss.ddd)}  maps to <?php echo htmlspecialchars($t->xxxx_xxxx($sss->ddd,$sss->ddd))?>
    * {t.xxxx_xxxx(#aaaa#,sss.ddd)}   maps to <?php echo htmlspecialchars($t->xxxx_xxxx("aaaa",$sss->ddd))?>
    * {t.xxxx_xxxx(sss.ddd,#aaaa#)}   maps to <?php echo htmlspecialchars($t->xxxx_xxxx($sss->ddd,"aaaa"))?>
    *
    *
    *
    * @param   string    $input the template
    * @return    string   the result of the filtering
    * @access   public
    */





    function methods($input) {

        /* no vars */
        $input = preg_replace_callback(
            "/".$this->start."([a-z0-9_.]+)\(\)(:({$this->modifiers}))?".$this->stop."/i",
           
           function($m) {
                return $this->_modifiers(
                    $this->error . '$' . str_replace('.','->',$m[1]) .'()',
                    empty($m[3]) ? '' : $m[3]
                );
            },
            $input
        );
           
        // more complex regex .. would make this less lines, but more confusing....
        
        
        /* single vars */
        
        $input = preg_replace_callback(
            "/".$this->start."([a-z0-9_.]+)\(([a-z0-9_.]+)\)(:({$this->modifiers}))?".$this->stop."/i",
           
           function($m) {
                return $this->_modifiers(
                    $this->error . '$' . str_replace('.','->',$m[1]) .'('.
                        '$'. str_replace('.','->',$m[2]) .
                    ')',
                    empty($m[4]) ? '' : $m[4]
                );
            },
            $input
        );
        
       $input = preg_replace_callback(
            "/".$this->start."([a-z0-9_.]+)\(\#([^\#]+)\#\)(:({$this->modifiers}))?".$this->stop."/i",
           
           function($m) {
                return $this->_modifiers(
                    $this->error . '$' . str_replace('.','->',$m[1]) .'('.
                        var_export($m[2]) .
                    ')',
                    empty($m[4]) ? '' : $m[4]
                );
            },
            $input
        );
        /* double vars     */
        
         
        $input = preg_replace_callback(
            "/".$this->start."([a-z0-9_.]+)\(([a-z0-9_.]+),([a-z0-9_.]+)\)(:({$this->modifiers}))?".$this->stop."/i",
           
           function($m) {
                return $this->_modifiers(
                    $this->error . '$' . str_replace('.','->',$m[1]) .'('.
                        '$'. str_replace('.','->',$m[2]) . ',' .
                        '$'. str_replace('.','->',$m[3]) . 
                    ')',
                    empty($m[5]) ? '' : $m[5]
                );
            },
            $input
        );
        
       
          /* double vars:: # #'d  ,var */
     
        $input = preg_replace_callback(
            "/".$this->start."([a-z0-9_.]+)\(\#([^\#]+)\#,([a-z0-9_.]+)\)(:({$this->modifiers}))?".$this->stop."/i",
           
           function($m) {
                return $this->_modifiers(
                    $this->error . '$' . str_replace('.','->',$m[1]) .'('.
                         var_export($m[2],true) . ',' .
                        '$'. str_replace('.','->',$m[3]) . 
                    ')',
                    empty($m[5]) ? '' : $m[5]
                );
            },
            $input
        );
        
          /* double vars:: var , # #'d  */
          
        $input = preg_replace_callback(
            "/".$this->start."([a-z0-9_.]+)\(([a-z0-9_.]+),\#([^\#]+)\#\)(:({$this->modifiers}))?".$this->stop."/i",
           
           function($m) {
                return $this->_modifiers(
                    $this->error . '$' . str_replace('.','->',$m[1]) .'('.
                        '$'. str_replace('.','->',$m[3]) . ',' .
                        var_export($m[2],true) .
                    ')',
                    empty($m[5]) ? '' : $m[5]
                );
            },
            $input
        );
        
        
        return $input;
    }
    /**
    * Looping
    *
    * This allows you to do loops on variables (eg. nested/ repeated blocks!)
    *
    * Maps Methods
    * {foreach:t.xyz,zzz}     maps to  <?php if ($i->xyz) foreach ($t->xyz as $zzz) { ?>
    * {foreach:t.xyz,xxx,zzz} maps to  <?php if ($i->xyz) foreach ($t->xyz as $xxx=>$zzz) { ?>
    * {end:}                  maps to  <?php }?>
    * {else:}                 maps to  <?php }else{?>
    *
    *
    *
    * @param    string    $input the template
    * @return   string    the result of the filtering
    * @access   public
    */


    function looping($input)
    {
        $input = preg_replace_callback(
            "/".$this->start."foreach:([a-z0-9_.]+),([a-z0-9_.]+)".$this->stop."/i",
            function($m) {
                return '<?php if ('.$this->error.'$' .
                        str_replace('.','->',$m[1])  . ') foreach(' . 
                        '$'. str_replace('.','->',$m[1]) . ' as ' .
                        '$'. str_replace('.','->',$m[2]) . ' { ?>';
            },
            $input
        );
        
        $input = preg_replace_callback(
            "/".$this->start."foreach:([a-z0-9_.]+),([a-z0-9_.]+),([a-z0-9_.]+)".$this->stop."/i",
            function($m) {
                return '<?php if ('.$this->error.'$' .
                        str_replace('.','->',$m[1])  . ') foreach(' . 
                        '$'. str_replace('.','->',$m[1]) . ' as ' .
                        '$'. str_replace('.','->',$m[2]) . ' => ' .  // not sure if '->' is really needed here...
                        '$'. str_replace('.','->',$m[3]) . ' { ?>';
            },
            $input
        );
        
        $input = str_replace(stripslashes($this->start)."else:".stripslashes($this->stop),'<?php }else{?>', $input);
        $input = str_replace(stripslashes($this->start)."end:".stripslashes($this->stop),'<?php }?>', $input);
        return $input;
    }
    /**
    * Conditional inclusion
    *
    * This allows you to do conditional inclusion (eg. blocks!)
    *
    * Maps conditions
    *
    * {if:t.xxxx}         => <?php if ($t->xxxx) { ?>
    * {if:t.x_xxx()}      => <?php if ($t->x_xxx()) { ?>
    *
    * @param    string   $input the template
    * @return   string   the result of the filtering
    * @access   public
    */

    function conditionals($input) {

        
        $input = preg_replace_callback(
            "/".$this->start."if:([a-z0-9_.]+)".$this->stop."/i",
            function($m) {
                return '<?php if ('.$this->error.'$' .
                        str_replace('.','->',$m[1])  . ')  { ?>';
            },
            $input
        );
        $input = preg_replace_callback(
            "/".$this->start."if:([a-z0-9_.]+)\(\)".$this->stop."/i",
            function($m) {
                return '<?php if ('.$this->error.'$' .
                        str_replace('.','->',$m[1])  . '())  { ?>';
            },
            $input
        );
         
        return $input;
    }
    /**
    * sub template inclusion
    *
    * This allows you to do include other files (either flat or generated templates.).
    *
    * {include:t.abcdef}    maps to  <?php
    *                       if($t->abcdef && file_exists($compileDir . "/". $t->abcdef . "en.php"))
    *                           include($compileDir . "/". $t->abcdef . ".en.php");
    *                       ?>
    *
    * include abcdef.en.php (Eg. hard coded compiled template
    * {include:#abcdef#}    => <?php
    *                       if(file_exists($compileDir . "/abcdef.en.php"))
    *                           include($compileDir . "/abcdef.en.php");
    *                       ?>
    *
    *  include raw
    * {t_include:#abcdef.html#}    => <?php
    *                       if(file_exists($templateDir . "/abcdef.html"))
    *                           include($compileDir . "/abcdef.html");
    *                       ?>
    *  Compile and include
    * {q_include:#abcdef.html#}    => <?php
    *                      HTML_Template_Flexy::staticQuickTemplate('abcedef.html',$t);
    *                       ?>
    *
    *
    * @param    string   $input the template
    * @return   string   the result of the filtering
    * @access   public
    */

    function include_template($input) {

        // array not supported for this type of template.
        if (is_array($this->engine->options['templateDir'])) {
            return $input;
        }
        
        $input = preg_replace_callback(
            "/".$this->start."include:([a-z0-9_.]+)".$this->stop."/i",
            function($m) {
                return  '<?php
                if (('.$this->error.'$' . str_replace('.','->',$m[1]) . ') &&
                    file_exists("' .  $this->engine->options['compileDir'] .
                    '/{$' .  str_replace('.','->',$m[1]) . '}.en.php"))
                include "' .  $this->engine->options['compileDir'] .
                    '/{$' .  str_replace('.','->',$m[1]) . '}.en.php");?>';
            },
            $input
        );
        
        
        $input = preg_replace_callback(
            "/".$this->start."include:#([a-z0-9_.]+)#".$this->stop."/i", 
            function($m) {
                return  '<?php
                if (file_exists("' .  $this->engine->options['compileDir'] .
                    '/' .  $m[1] .'.en.php"))
                include "' .  $this->engine->options['compileDir'] .
                    '/' .  $m[1]. '.en.php");?>';
            },
            $input
        );
        
        $input = preg_replace_callback(
            "/".$this->start."include:#([a-z0-9_.]+)#".$this->stop."/i", 
            function($m) {
                return  '<?php
                if (file_exists("' .  $this->engine->options['templateDir'] .
                    '/' .  $m[1] . '"))
                include "' .  $this->engine->options['templateDir'] .
                    '/' .  $m[1]. '");?>';
            },
            $input
        );
        
        $input = preg_replace_callback(
            "/".$this->start."include:#([a-z0-9_.]+)#".$this->stop."/i", 
            function($m) {
                return  '<?php
                HTML_Template_Flexy::staticQuickTemplate("'.  $m[1] . '",$t);?>';
            },
            $input
        );
        return $input;
    }
    
    
    var $modifiers = "h|u|ru|r|n|uu|s";
    
    
    function _modifiers ($val, $mod)
    {
        switch($mod) {
            case 'h':
                return '<?php echo ' . $val . ';?>';
            
            case 'u':
                return '<?php echo urlencode(' . $val . ');?>';
            
            case 'uu':
                return '<?php echo urlencode(urlencode(' . $val . '));?>';
            
            case 'ru':
                return '<?php echo rawurlencode(' . $val . ');?>';
            
            case 'r':
                return '<?php   print_r(' . $val . ');?>';
            
            case 'n':
                return '<?php  nl2br(htmlspecialchar(' . $val . '));?>';
            
            case 's':
                return '<?php  highlight_string(' . $val . ');?>';
           
            default:
                return '<?php echo htmlspecialchars(' . $val . ');?>';
        }
    }




}
 