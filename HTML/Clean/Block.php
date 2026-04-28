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
        if (!$db) {
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
            return;
        }
        $ar = $body->getElementsByTagName($type);
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
        $o = $this->toObject();
        $n = empty($node) ? $this->node : $node;
        $el = self::createDom($o, false, $n->ownerDocument);
        $node->parentNode->replaceChild($el, $node);
        
    }
    /**
     * used by readElement to extract data from a node
     * may need improving as it's pretty basic
     
     * @param {DomElement} node
     * @param {String} tag - tag to find, eg. IMG ?? might be better to use DomQuery ?
     * @param {String} attribute (use html - for contents, style for using next param as style, or false to return the node)
     * @param {String} style the style property - eg. text-align
     */
    function getVal($node, $tag , $attr = false, $style = false)
    {
        $n = $node;
        if ($tag !== true && $n->tagName != strtolower($tag)) {
            $n = $node->getElementsByTagName($tag)->item(0);
        }
        if (!$n) {
            return '';
        }
        if ($attr === false) {
            return $n;
        }
        if ($attr == 'html') {
            return $this->innerHTML($n);
        }

        if ($attr == 'style') {
            $styles = $this->styleToArray($n);
            return isset($styles[strtolower($style)]) ? $styles[strtolower($style)] : '';
        }
        
        return $n->hasAttribute($attr) ? $n->getAttribute($attr) : '';
            
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
    
    static function createDom ($o, $parentNode = false, $doc = false) {
        if($doc === false) {
            $doc = new DOMDocument('1.0', 'utf8');
        }
        
        if (is_string($o)) {
            return  $parentNode ? $parentNode->appendChild($doc->createTextNode($o)) : $doc->createTextNode($o);
        }
        if(empty($o['tag'])){
            $o['tag'] = "div";
        }
        
        $el = $doc->createElement($o['tag']);
        
        foreach ($o as $attr => $val) {
            
            if($attr == "tag" || $attr == "ns" ||$attr == "xmlns" || $attr == "children" || $attr == "cn" || $attr == "html" || 
                    $attr == "style") {
                continue;
            }

            // skip empty attribute
            if(empty($val)) {
                continue;
            }
                    
            if ($attr=="cls"){
                $el->setAttribute('class',$val);
            } else {
                $el->setAttribute($attr, $val);
            }
        }
        if (isset($o['style'])) {
            $styles = array();
            foreach($o['style'] as $k=>$v) {
                $styles[] = "$k:$v";
            }
            $el->setAttribute('style', implode(";", $styles));
        }
        $cn = isset($o['cn']) ? $o['cn'] :
                (isset($o['children']) ? $o['children'] : false);
           
        if($cn) {
            //http://bugs.kde.org/show_bug.cgi?id=71506
            if (is_array($cn)) {
                foreach($cn as $c) {
                    self::createDom($c, $el, $doc);
                }
            }else{
                self::createDom($cn, $el, $doc);
            }
        }
        if(isset($o['html'])) {
            $f = $doc->createDocumentFragment();
            $f->appendXML($o['html']);
            $el->appendChild($f);
        }
        if($parentNode){
           $parentNode->appendChild($el);
        }

        return $el;
    } 
};
