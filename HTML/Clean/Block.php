<?php

/**
 * This is used in the HTML editor to make content editable
 *
 * In our case it's used to render images and tables correctly.
 */
class  HTML_Clean_Block
{
    var $node;
   
    // used by context menu
    var $context = false; // ??
   
    function __construct($cfg)
    {
        foreach($cfg as $k=>$v) {
            $this->$k = $v;
        }
    }

         
    
    static function factory ($node)
    {
         
        
        $db  = $node->hasAttribute('data-block') ? $node->getAttribute('data-block') : false;
        if ($db) {
            $db = ucfirst($node->nodeName);
        }
        require_once 'HTML/Clean/Block'.$db . '.php';
        $cls = 'HTML_Clean_Block'. $db;
        
        return new $cls(array('node' => $node ));
        
    }
    

    /**
     * initalize all Elements from content that are 'blockable'
     * @static
     * @param the body element
     */
    static function initAll ($body, $type=false)
    {
        if ($type === false) {
            
            self::initAll($body,'table');
            self::initAll($body,'td');
            self::initAll($body,'figure');
            return;
        }
        $ar = $body->getElementsByName($type);
        foreach($ar as $a) {
            self::factory($a);
        }
        
    }
Roo.htmleditor.Block.prototype = {
    
    node : false,
    
     // used by context menu
    friendly_name : 'Based Block',
    
    // text for button to delete this element
    deleteTitle : false,
    
    context : false,
    /**
     * Update a node with values from this object
     * @param {DomElement} node
     */
    updateElement : function(node)
    {
        Roo.DomHelper.update(node === undefined ? this.node : node, this.toObject());
    },
     /**
     * convert to plain HTML for calling insertAtCursor..
     */
    toHTML : function()
    {
        return Roo.DomHelper.markup(this.toObject());
    },
    /**
     * used by readEleemnt to extract data from a node
     * may need improving as it's pretty basic
     
     * @param {DomElement} node
     * @param {String} tag - tag to find, eg. IMG ?? might be better to use DomQuery ?
     * @param {String} attribute (use html - for contents, style for using next param as style, or false to return the node)
     * @param {String} style the style property - eg. text-align
     */
    getVal : function(node, tag, attr, style)
    {
        var n = node;
        if (tag !== true && n.tagName != tag.toUpperCase()) {
            // in theory we could do figure[3] << 3rd figure? or some more complex search..?
            // but kiss for now.
            n = node.getElementsByTagName(tag).item(0);
        }
        if (!n) {
            return '';
        }
        if (attr === false) {
            return n;
        }
        if (attr == 'html') {
            return n.innerHTML;
        }
        if (attr == 'style') {
            return n.style[style]; 
        }
        
        return n.hasAttribute(attr) ? n.getAttribute(attr) : '';
            
    },
    /**
     * create a DomHelper friendly object - for use with 
     * Roo.DomHelper.markup / overwrite / etc..
     * (override this)
     */
    toObject : function()
    {
        return {};
    },
      /**
     * Read a node that has a 'data-block' property - and extract the values from it.
     * @param {DomElement} node - the node
     */
    readElement : function(node)
    {
        
    } 
    
    
};
