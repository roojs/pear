<?php

/**
 * This is a PHP implementation of the Roo HTMLEditorCore onPaste method - that cleans up HTML
 * and replaces things like tables etc..
 */

class HTML_Clean {
    
    static function fromHTML($str, $opts = array())
    {
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
        'COLGROUP', 'COL'   // messy tables.
    ); // blacklist of elements.
    
    function __construct($opts)
    {
        foreach($opts as $k=>$v) {
            $this->{$k} = $v;
        }
        $d = $this->dom->documentElement;
        $this->filter('Word',array( 'node' =>  $d ));
            
        $this->filter('StyleToTag', array(
            'node' =>  $d   // this could add nodes to tree, so not very good to nest the walk.
            
        ));
        
        $this->filter('Attributes',array(    // does walk as well.
            'node' => $d,
            'attrib_white' => array('href', 'src', 'name', 'align', 'colspan', 'rowspan', 'data-display', 'data-width', 'start'),
            'attrib_clean' => array('href', 'src' ),
            
            'replaceComment' => true   // this is sneaked in here - as walk will get rid of comments at the same time.
        ));
        // is this used?!?!
        $this->filter('Black', array( 'node' =>  $d, 'tag'  =>  $this->black ));
        // we don't use the whitelist?
        
        
        // should be fonts..
        $this->filter('KeepChildren',array( 'node' =>  $d, 'tag'  =>   array(   'FONT', ':' )) );  
        $this->filter('Paragraph',array( 'node' =>  $d ));
        $this->filter('Span',array( 'node' =>  $d ));
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
            
        }
         
        
        
        require_once 'HTML/Clean/Block.php';
        HTML_Clean_Block::initAll($d);

    }
    
    function filter($type, $args)
    {
        require_once 'HTML/Clean/Filter'. $type .'.php';
        $cls = 'HTML_Clean_Filter'. $type;
        new $cls($args);
    }
    
    function toString()
    {
        $this->dom->saveHTML();
    }
    
    
}