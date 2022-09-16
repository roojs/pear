<?php

/**
 * This is used in the HTML editor to make content editable
 *
 * In our case it's used to render images and tables correctly.
 */
require_once 'Filter.php';
abstract class  HTML_Clean_Block extends HTML_Clean_Filter
{
    var $node;
   
    // used by context menu
    var $context = false; // ??
   
    

         
    
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
     /**
     * Update a node with values from this object
     * @param {DomElement} node
     */
    function updateElement ($node)
    {
        self::updateNode(empty($node) ? $this->node : $node, self::createDom($this->toObject()));
        
    }
     /**
     * convert to plain HTML for calling insertAtCursor..
     */
    function toHTML ()
    {
        return self::createHTML($this->toObject());
    }
    /**
     * used by readEleemnt to extract data from a node
     * may need improving as it's pretty basic
     
     * @param {DomElement} node
     * @param {String} tag - tag to find, eg. IMG ?? might be better to use DomQuery ?
     * @param {String} attribute (use html - for contents, style for using next param as style, or false to return the node)
     * @param {String} style the style property - eg. text-align
     */
    function getVal($node, $tag , $attr = false, $style = false)
    {
         $n = $node;
        if ($tag !== true && $n->tagName != strtoupper($tag)) {
            // in theory we could do figure[3] << 3rd figure? or some more complex search..?
            // but kiss for now.
            $n = $node->getElementsByTagName($tag)->item(0);
        }
        if (!$n) {
            return '';
        }
        if ($attr === false) {
            return $n;
        }
        if ($attr == 'html') {
            $ret = '';
            foreach ($node->childNodes as $child) {
                $ret.= $child->ownerDocument->saveXML( $child );
            }
        
            return $ret;
            
            
        }
        if ($attr == 'style') {
            $style = $this->styleToObject($node, true);
            return isset($style[strtolower($style)]) ? $style[strtolower($style)] : '';
        }
        
        return n->hasAttribute($attr) ? n->getAttribute($attr) : '';
            
    }
    /**
     * create a DomHelper friendly object - for use with 
     * Roo.DomHelper.markup / overwrite / etc..
     * (override this)
     */
    abstract function toObject();
      /**
     * Read a node that has a 'data-block' property - and extract the values from it.
     * @param {DomElement} node - the node
     */
    abstract function readElement ($node);
    
    static function createHTML($o)
    {
        
        if (is_string($o)) {
            return $o;
        }
        $b  = "";
        if(empty($o->tag)){
            $o->tag = "div";
        }
        $b .= "<" . $o->tag;
        
        foreach($o as $attr => $val) {
            if ($attr == "tag" || $attr == "children" || $attr == "cn" || $attr == "html") {
                continue;
            }
            if($attr == "style"){
                
                if (is_string($val)) {
                    $b .= ' style="' . $val . '"';
                } else if(is_array($val)) {
                    $b .= ' style="';
                    foreach($val as $kk=>$vv) {
                        $b .= $kk . ":" . $vv . ";";
                    
                    }
                    $b .= '"';
                }
            } else {
                if($attr == "cls"){
                    $b .= ' class="' + $val + '"';
                }else if($attr == "htmlFor"){
                    $b .= ' for="' + $val + '"';
                } else {
                    $b .= " " + $attr + '="' + $val + '"';
                }
            }
        }
        if (preg_match('/^(?:br|frame|hr|img|input|link|meta|range|spacer|wbr|area|param|col)$/i', $o->tag)) {
            $b .= "/>"; // empty
        } else { 
            $b .= ">";
            $cn = isset($o->cn) ? $o->cn :
                (isset($o->children) ? $o->children : false);
            
            if($cn !== false){
                
                if(is_array($cn)) {
                    foreach($cn as $v) {
                        $b .= self::createHtml($v);
                    }
                }else{
                    $b .= self::createHtml(cn);
                }
            }
            if(isset($o->html)){
                $b .= $o->html;
            }
            $b .= "</" + $o->tag + ">";
        }
        return $b;
         
        
    }
    
    static function createDom ($o, $parentNode=false){
         
        // defininition craeted..
        var ns = false;
        if (o.ns && o.ns != 'html') {
               
            if (o.xmlns && typeof(xmlns[o.ns]) == 'undefined') {
                xmlns[o.ns] = o.xmlns;
                ns = o.xmlns;
            }
            if (typeof(xmlns[o.ns]) == 'undefined') {
                console.log("Trying to create namespace element " + o.ns + ", however no xmlns was sent to builder previously");
            }
            ns = xmlns[o.ns];
        }
        
        
        if (typeof(o) == 'string') {
            return parentNode.appendChild(document.createTextNode(o));
        }
        o.tag = o.tag || div;
        if (o.ns && Roo.isIE) {
            ns = false;
            o.tag = o.ns + ':' + o.tag;
            
        }
        var el = ns ? document.createElementNS( ns, o.tag||'div') :  document.createElement(o.tag||'div');
        var useSet = el.setAttribute ? true : false; // In IE some elements don't have setAttribute
        for(var attr in o){
            
            if(attr == "tag" || attr == "ns" ||attr == "xmlns" ||attr == "children" || attr == "cn" || attr == "html" || 
                    attr == "style" || typeof o[attr] == "function") { continue; }
                    
            if(attr=="cls" && Roo.isIE){
                el.className = o["cls"];
            }else{
                if(useSet) { el.setAttribute(attr=="cls" ? 'class' : attr, o[attr]);}
                else { 
                    el[attr] = o[attr];
                }
            }
        }
        Roo.DomHelper.applyStyles(el, o.style);
        var cn = o.children || o.cn;
        if(cn){
            //http://bugs.kde.org/show_bug.cgi?id=71506
             if((cn instanceof Array) || (Roo.isSafari && typeof(cn.join) == "function")){
                for(var i = 0, len = cn.length; i < len; i++) {
                    createDom(cn[i], el);
                }
            }else{
                createDom(cn, el);
            }
        }
        if(o.html){
            el.innerHTML = o.html;
        }
        if(parentNode){
           parentNode.appendChild(el);
        }
        return el;
    };
    
};
