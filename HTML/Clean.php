<?php

/**
 * This is a PHP implementation of the Roo HTMLEditorCore onPaste method - that cleans up HTML
 * and replaces things like tables etc..
 */

class HTML_Clean {
    
    static function fromHTML($str, $opts = array())
    {
        // $str = "<body lang='ar'><h1>aaa</h1><P></P><p dir='ltr'><b>Hello</b></p><span dir='rtl'>World</span></body>";
        $str= self::cleanWordChars($str);
        $dom = new DOMDocument('1.0', 'utf8');
        $dom->loadHTML($str);
        $opts['dom'] = $dom;
        return new HTML_Clean($opts);    
    }
    static function cleanWordChars($str)
    {
        $swapCodes  = array(
             8211 =>  "&#8211;" ,  
             8212 =>  "&#8212;" ,  
             8216 =>   "'" ,   
             8217 =>  "'" ,   
             8220 =>  '"' ,   
             8221 =>  '"' ,   
             8226 =>  "*" ,   
             8230 =>  "..." 
        );
        foreach($swapCodes as $k=>$v) {
            $str = str_replace(mb_chr($k), $v, $str);
        }
        return $str;
    
    }
    
    
    var $dom; // Dom Document.
    var $black = array(
        'APPLET', // 
        'BASE',   'BASEFONT', 'BGSOUND', 'BLINK',  'BODY', 
        'FRAME',  'FRAMESET', 'HEAD',    'HTML',   'ILAYER', 
        'IFRAME', 'LAYER',  'LINK',     'META',    'OBJECT',   
        'SCRIPT', 'STYLE' ,'TITLE',  'XML',
        //'FONT' // CLEAN LATER..
        'COLGROUP', 'COL',   // messy tables.
        'SDFIELD' // generated when extracting html from word using 'libreoffice'
    ); // blacklist of elements.
    
    function __construct($opts)
    {
        foreach($opts as $k=>$v) {
            $this->{$k} = $v;
        }
        $d = $this->dom->getElementsByTagName('body')->item(0);
        if (!$d) {
            // no body?
            return;
        }
        $language = $d->getAttribute('lang') ?: 'en';

        // var_dump($this->dom);
        $this->filter('Word',array( 'node' =>  $d ));
            
        $this->filter('StyleToTag', array(
            'node' =>  $d   // this could add nodes to tree, so not very good to nest the walk. 
        ));
        
        $this->filter('Attributes',array(    // does walk as well.
            'node' => $d,
            // 'attrib_white' => array('href', 'src', 'name', 'align', 'colspan', 'rowspan', 'data-display', 'data-width', 'start'),
            'attrib_white' => array(
                'href',
                'src',
                'name',
                'align',
                'colspan',
                'rowspan',
                'start',
                'dir'
            ),
            'attrib_clean' => array('href', 'src' ),
            
            'replaceComment' => true,   // this is sneaked in here - as walk will get rid of comments at the same time.
            'lang' => $language
        ));

        // is this used?!?!
        $this->filter('Black', array( 'node'=> $d, 'tag'  => $this->black));
        // we don't use the whitelist?
        
        // should be fonts..
        $this->filter('KeepChildren',array( 'node' =>  $d, 'tag'  =>   array(   'FONT', ':' )) );  
        $this->filter('Paragraph',array( 'node' =>  $d, 'lang' => $language ));
        $this->filter('Span',array( 'node' =>  $d ));
        $result = '';
        foreach ($d->childNodes as $child) {
            $result .= ($this->dom->saveHTML($child));
        }
        $this->filter('LongBr',array( 'node' =>  $d ));


        $ar = $this->arrayFrom($d->getElementsByTagName('img'));
        foreach($ar as $img) {
            if ($this->findParent($img, 'figure')) {
                continue;
            }
            require_once 'HTML/Clean/BlockFigure.php';
            $fig = new HTML_Clean_BlockFigure(array(
                'image_src' => $img->getAttribute('src')
            ));
            $fig->updateElement($img);   
            die('test2');
        }

        $result = '';
        foreach ($d->childNodes as $child) {
            $result .= ($this->dom->saveHTML($child));
        }
        var_dump("RESULT");
        var_dump($result);
        die('test8');
         
        
        
        require_once 'HTML/Clean/Block.php';
        HTML_Clean_Block::initAll($d);

    }

    function filter($type, $args)
    {
        require_once 'HTML/Clean/Filter'. $type .'.php';
        $cls = 'HTML_Clean_Filter'. $type;
        new $cls($args);
    }

    function arrayFrom($list)
    {
        $ret = array();
        foreach($list as $k=> $l) {
            $ret[$k] = $l;
        }
        return $ret;
    }
    
    /**
     * Find a parent element with the specified tag name
     * Traverses up the DOM tree from the given node
     * 
     * @param DOMNode $node The node to start searching from
     * @param string $tagName The tag name to search for (case-insensitive)
     * @param int $maxDepth Maximum depth to search (default: 50)
     * @return DOMElement|null The matching parent element or null if not found
     */
    function findParent($node, $tagName, $maxDepth = 50)
    {
        if (!$node) {
            return null;
        }
        
        $parent = $node->parentNode;
        $depth = 0;
        $tagNameLower = strtolower($tagName);
        
        while ($parent && $parent->nodeType == XML_ELEMENT_NODE && $depth < $maxDepth) {
            // Check if this is the body element (stop here)
            if (strtolower($parent->tagName) === 'body') {
                break;
            }
            
            // Check if tag name matches (case-insensitive)
            if (strtolower($parent->tagName) === $tagNameLower) {
                return $parent;
            }
            
            $depth++;
            $parent = $parent->parentNode;
        }
        
        return null;
    }
    
    function toString()
    {
        $this->dom->saveHTML();
    }
    
    
}