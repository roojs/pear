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
// $Id: php_pear_headers,v 1.1 2002/04/22 09:51:27 alan_k Exp $
//
// This is a wrapper class for XML_Tree to lets you add callbacks to xml tags
// to enable data morphing (so you can get associative arrays and the like from 
// Trees.
// 
//

require_once 'XML/Tree.php';

/**
* The Morpher..
*
* Usage:
*
*
*
*
* require_once 'XML/Tree/Morph.php';
*
*
* $x = new XML_Tree_Morph(
*    'somefile.glade',
*    array(
*       'debug' => 0,
*       'filter' => array(
*            //tag      // either toObject/toArray or your function/method etc.
*           'project' => 'toObject',
*           'widget'  => 'toObject',
*           'child'   => 'toObject',
*
*         )
*     )
* );
* $tree = $x->getTreeFromFile();
* print_r($tree->children[0]['project']);
* print_r($tree->children[1]['widget']);
* 
* Results in...
*
*stdClass Object
*(
*    [name] => validatemanager
*    [program_name] => validatemanage 
*    [directory] =>
*    [source_directory] => src
*    [pixmaps_directory] => pixmaps
*    [language] => C
*    [gnome_support] => False
*    [gettext_support] => False
*)
*stdClass Object
*(
*    [class] => GtkWindow
*    [name] => window
*    [title] => Gtk_ValidateManager
*    [type] => GTK_WINDOW_TOPLEVEL
*    [position] => GTK_WIN_POS_CENTER
*    [modal] => False
*    [default_width] => 600
*    [default_height] => 400
*    [allow_shrink] => False
*    [allow_grow] => True
*    [auto_shrink] => False
*    [widget] => stdClass Object
*        ( ......
*
*
* @version    $Id: php_phpdoc_class,v 1.1 2002/04/22 10:20:29 alan_k Exp $
*/
class XML_Tree_Morph extends XML_Tree {


    /**
    * Constructor
    *
    * 
    * 
    * 
    * @param   string               Filename
    * @param   array $options
    *                   valid options:
    *               debug = 0/1
    *               filter => array(
    *                   tagname => callback
    *   
    *           where callback can be
    *               - toObject|toArary = built in converters
    *               - 'function', array($object,'method') or
    *                 array('class','staticMethod');
    * 
    *
    * @access   public
    */
  
    function XML_Tree_Morph($filename,$options) {
       
        XML_Tree::XML_Tree($filename,'1.0');
        
        $this->_morphOptions = $options;
    }
    

    /**
    * Overridden endHandler which relays into callbacks..
    *
    * @see      XML_Tree:endHandler();
    */
      
  
    
    function endHandler($xp, $elem)  {
        $this->i--;
        if ($this->i > 1) {
            $obj_id = 'obj' . $this->i;
            // recover the node created in StartHandler
            $node   =  $this->$obj_id;
            // mixed contents
            if (count($node->children) > 0) {
                if (trim($this->cdata)) {
                    $node->children[] =  new XML_Tree_Node(null, $this->cdata);
                }
            } else {
                $node->setContent($this->cdata);
            }
            $parent_id = 'obj' . ($this->i - 1);
            $parent    = $this->$parent_id;
            // attach the node to its parent node children array
            
            if (isset($this->_morphOptions['filter'][$node->name])) {
                $f = &$this->_morphOptions['filter'][$node->name];
                if (is_string($f) && method_exists($this,'morph'.$f)) {
                    $parent->children[] = $this->{'morph'.$this->_morphOptions['filter'][$node->name]}($node);
                    $this->cdata = null;
                    return null;
                }
                if (is_callable($f)) {
                    $parent->children[] = call_user_func($f, $node);
                    $this->cdata = null;
                    return null;
                }
                 
                
                
            }
            if (@$this->_morphOptions['debug']) {
                echo "SKIP: {$node->name}\n";
            }
            
            $parent->children[] = $node;
        }
        $this->cdata = null;
        return null;
        
         
    }
    
    /**
    * morph to an array
    *
    * Converts standard <xxx>vvvv</xxxx> into
    *   [xxxx] => vvvvv
    * 
    * 
    * @param   object XML_Tree_Node
    * 
    *
    * @return   array (name => array(keys=>values)
    * @access   public
    */
    function morphToArray($node) {
        $ret = array();
        foreach($node->children as $cnode)  {
            // is cnode a node?
            if (is_a($cnode,'xml_tree_node')) {
                $ret[$cnode->name] = $cnode->content;
                continue;
            }
            // otherwise it's an array...
             
            
            foreach($cnode as $k=>$o) {
                if (empty($ret[$k])) {
                    $ret[$k]= array();
                }
                if (!is_array($ret[$k])) {
                    echo "OPPS: $k already in array ";print_r($ret);
                    echo "TRYING TO ADD "; print_r($cnode);
                    exit;
                }
                $ret[$k][] = $o;
            }
            
        }
        foreach($ret as $k=>$v) {
            if (is_array($v) && count($v) == 1 ) {
                $ret[$k] = $v[0];
            }
        }
        
        //print_r($ret);
        return array($node->name => $ret);
    }
      /**
    * morph to an object
    *
    * Converts standard <xxx>vvvv</xxxx> into
    *   $obj->xxx = vvvvv
    * 
    * 
    * @param   object XML_Tree_Node
    * 
    *
    * @return   array   ($node->name => $object);
    * @access   public
    */
    function morphToObject($node) {
        $ret = new StdClass;
        foreach($node->children as $cnode)  {
            // is cnode a node?
            if (is_a($cnode,'xml_tree_node')) {
                $ret->{$cnode->name} = $cnode->content;
                continue;
            }
            // otherwise it's an array...
             
            
            foreach($cnode as $k=>$o) {
                if (empty($ret->{$k})) {
                    $ret->{$k}= array();
                }
                if (!is_array($ret->{$k})) {
                    echo "OPPS: $k already in array ";print_r($ret);
                    echo "TRYING TO ADD "; print_r($cnode);
                    exit;
                }
                $ret->{$k}[] = $o;
            }
            
        }
        foreach($ret as $k=>$v) {
            if (is_array($v) && count($v) == 1 ) {
                $ret->{$k} = $v[0];
            }
        }
        
        //print_r($ret);
        return array($node->name => $ret);
    }
}
 