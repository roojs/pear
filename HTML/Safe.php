<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Loosely Based onHTML_Safe Parser
 *
 * PHP versions 4 and 5
 *
 * @category   HTML
 * @package    HTML_Safe
 * @author     Roman Ivanov <thingol@mail.ru>
 * @copyright  2004-2005 Roman Ivanov
 * @license    http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @version    CVS: $Id:$
 * @link       http://pear.php.net/package/HTML_Safe
 */
 
/**
 *
 * HTML_Safe Parser
 *
 * This parser strips down all potentially dangerous content within HTML:
 * <ul>
 * <li>opening tag without its closing tag</li>
 * <li>closing tag without its opening tag</li>
 * <li>any of these tags: "base", "basefont", "head", "html", "body", "applet", 
 * "object", "iframe", "frame", "frameset", "script", "layer", "ilayer", "embed", 
 * "bgsound", "link", "meta", "style", "title", "blink", "xml" etc.</li>
 * <li>any of these attributes: on*, data*, dynsrc</li>
 * <li>javascript:/vbscript:/about: etc. protocols</li>
 * <li>expression/behavior etc. in styles</li>
 * <li>any other active content</li>
 * </ul>
 * It also tries to convert code to XHTML valid, but htmltidy is far better 
 * solution for this task.
 *
 * <b>Example:</b>
 * <pre>
 * $parser =& new HTML_Safe();
 * $result = $parser->parse($doc);
 * </pre>
 *
 * @category   HTML
 * @package    HTML_Safe
 * @author     Roman Ivanov <thingol@mail.ru>
 * @copyright  1997-2005 Roman Ivanov
 * @license    http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/HTML_Safe
 */
class HTML_Safe 
{
    
     
   
    /**
     * Array of prepared regular expressions for protocols (schemas) matching
     *
     * @var array
     * @access private
     */
    var $_protoRegexps = array();
    
    /**
     * Array of prepared regular expressions for CSS matching
     *
     * @var array
     * @access private
     */
    var $_cssRegexps = array();

    /**
     * List of single tags ("<tag />")
     *
     * @var array
     * @access public
     */
    var $singleTags = array('area', 'br', 'img', 'input', 'hr', 'wbr', );

    /**
     * List of dangerous tags (such tags will be deleted)
     *
     * @var array
     * @access public
     */
    var $deleteTags = array(
        'applet', 'base',   'basefont', 'bgsound', 'blink',  'body', 
        'embed',  'frame',  'frameset', 'head',    'html',   'ilayer', 
        'iframe', 'layer',  'link',     'meta',    'object', 'style', 
        'title',  'script', 
        );

    /**
     * List of dangerous tags (such tags will be deleted, and all content 
     * inside this tags will be also removed)
     *
     * @var array
     * @access public
     */
    var $deleteTagsContent = array('script', 'style', 'title', 'xml', );

    /**
     * Type of protocols filtering ('white' or 'black')
     *
     * @var string
     * @access public
     */
    var $protocolFiltering = 'white';

    /**
     * List of "dangerous" protocols (used for blacklist-filtering)
     *
     * @var array
     * @access public
     */
    var $blackProtocols = array(
        'about',   'chrome',     'data',       'disk',     'hcp',     
        'help',    'javascript', 'livescript', 'lynxcgi',  'lynxexec', 
        'ms-help', 'ms-its',     'mhtml',      'mocha',    'opera',   
        'res',     'resource',   'shell',      'vbscript', 'view-source', 
        'vnd.ms.radio',          'wysiwyg', 
        );

    /**
     * List of "safe" protocols (used for whitelist-filtering)
     *
     * @var array
     * @access public
     */
    var $whiteProtocols = array(
        'ed2k',   'file', 'ftp',  'gopher', 'http',  'https', 
        'irc',    'mailto', 'news', 'nntp', 'telnet', 'webcal', 
        'xmpp',   'callto',
        );

    /**
     * List of attributes that can contain protocols
     *
     * @var array
     * @access public
     */
    var $protocolAttributes = array(
        'action', 'background', 'codebase', 'dynsrc', 'href', 'lowsrc', 'src', 
        );

    /**
     * List of dangerous CSS keywords
     *
     * Whole style="" attribute will be removed, if parser will find one of 
     * these keywords
     *
     * @var array
     * @access public
     */
    var $cssKeywords = array(
        'absolute', 'behavior',       'behaviour',   'content', 'expression', 
        'fixed',    'include-source', 'moz-binding',
        );

    /**
     * List of tags that can have no "closing tag"
     *
     * @var array
     * @access public
     * @deprecated XHTML does not allow such tags
     */
    var $noClose = array();

    /**
     * List of block-level tags that terminates paragraph
     *
     * Paragraph will be closed when this tags opened
     *
     * @var array
     * @access public
     */
    var $closeParagraph = array(
        'address', 'blockquote', 'center', 'dd',      'dir',       'div', 
        'dl',      'dt',         'h1',     'h2',      'h3',        'h4', 
        'h5',      'h6',         'hr',     'isindex', 'listing',   'marquee', 
        'menu',    'multicol',   'ol',     'p',       'plaintext', 'pre', 
        'table',   'ul',         'xmp',
        'hgroup', 'header'
        );

    /**
     * List of table tags, all table tags outside a table will be removed
     *
     * @var array
     * @access public
     */
    var $tableTags = array(
        'caption', 'col', 'colgroup', 'tbody', 'td', 'tfoot', 'th', 
        'thead',   'tr', 
        );

    /**
     * List of list tags
     *
     * @var array
     * @access public
     */
    var $listTags = array('dir', 'menu', 'ol', 'ul', 'dl', );

    /**
     * List of dangerous attributes
     *
     * @var array
     * @access public
     */
    var $attributes = array('dynsrc', 'id', 'name', );

    /**
     * List of allowed "namespaced" attributes
     *
     * @var array
     * @access public
     */
    var $attributesNS = array('xml:lang', );

    /**
     * Constructs class
     *
     * @access public
     */
    function HTML_Safe($opts = array()) 
    {
        
        foreach ($opts as $k =>$v) {
            $this->$k = $v;
        }
        
        //making regular expressions based on Proto & CSS arrays
        foreach ($this->blackProtocols as $proto) {
            $preg = "/[\s\x01-\x1F]*";
            for ($i=0; $i<strlen($proto); $i++) {
                $preg .= $proto{$i} . "[\s\x01-\x1F]*";
            }
            $preg .= ":/i";
            $this->_protoRegexps[] = $preg;
        }

        foreach ($this->cssKeywords as $css) {
            $this->_cssRegexps[] = '/' . $css . '/i';
        }
        return true;
    }

    /**
     * Handles the writing of attributes - called from $this->_openHandler()
     *
     * @param array $attrs array of attributes $name => $value
     * @return boolean
     * @access private
     */
    function _writeAttrs ($attrs) 
    {
        $ret = '';
        if (is_array($attrs)) {
            foreach ($attrs as $name => $value) {

                $name = strtolower($name);

                if (strpos($name, 'on') === 0) {
                    continue;
                }
                if (strpos($name, 'data') === 0) {
                    continue;
                }
                if (in_array($name, $this->attributes)) {
                    continue;
                }
                if (!preg_match("/^[a-z0-9]+$/i", $name)) {
                    if (!in_array($name, $this->attributesNS)) {
                        continue;
                    }
                }

                if (($value === TRUE) || (is_null($value))) {
                    $value = $name;
                }

                if ($name == 'style') {
                   
                   // removes insignificant backslahes
                   $value = str_replace("\\", '', $value);

                   // removes CSS comments
                    while (1)
                    {
                        $_value = preg_replace("!/\*.*?\*/!s", '', $value);
                        if ($_value == $value) break;
                        $value = $_value;
                    }
                   
                    // replace all & to &amp;
                    $value = str_replace('&amp;', '&', $value);
                    $value = str_replace('&', '&amp;', $value);
                    $value = $this->cleanStyle($value);
                }
                
                $tempval = preg_replace_callback('/&#(\d+);?/m', function($m) { return  chr($m[0]); } , $value); //"'
                $tempval = preg_replace_callback('/&#x([0-9a-f]+);?/mi', function($m) { return chr(hexdec($m[0])); } , $tempval);

                
                ///$tempval = preg_replace('/&#(\d+);?/me', "chr('\\1')", $value); //"'
                ///$tempval = preg_replace('/&#x([0-9a-f]+);?/mei', "chr(hexdec('\\1'))", $tempval);

                if ((in_array($name, $this->protocolAttributes)) && 
                    (strpos($tempval, ':') !== false)) 
                {
                    if ($this->protocolFiltering == 'black') {
                        foreach ($this->_protoRegexps as $proto) {
                            if (preg_match($proto, $tempval)) continue 2;
                        }
                    } else {
                        $_tempval = explode(':', $tempval);
                        $proto = $_tempval[0];
                        if (!in_array($proto, $this->whiteProtocols)) {
                            continue;
                        }
                    }
                }

                $value = str_replace("\"", "&quot;", $value);
                $ret .= ' ' . $name . '="' . $value . '"';
            }
        }
        return $ret;
    }
    
    function cleanStyle ($str)
    {
        static $is = false;
        if (!$is) {
            require_once 'HTML/CSS/InlineStyle.php';
            $is = new HTML_CSS_InlineStyle();
        }
        $ar = $is->_styleToArray($str);
        foreach($ar as $k=>$v) {
            if (in_array(strtolower(trim($k)), $this->cssKeywords)) {
                //echo "Trashing BL css keyword $k=$v <br/>";
                unset($ar[$k]);
                continue;
            }
            foreach ($this->_protoRegexps as $proto) {
                if (preg_match($proto, $v)) {
                    echo "$proto - Trashing $k=$v <br/>";
                    unset($ar[$k]);
                    continue 2;
                }
            }
             
        }
        $st = array();
        foreach($ar as $prop => $val) {
            $st[] = "{$prop}:{$val}";
        }
        return implode(';', $st);
        
    }
    

    /**
     * Opening tag handler - called from HTMLSax
     *
     * @param object $parser HTML Parser
     * @param string $name   tag name
     * @param array  $attrs  tag attributes
     * @return boolean
     * @access private
     */
    function _openHandler($name, $attrs) 
    {
        $name = strtolower($name);

        if (in_array($name, $this->deleteTagsContent)) {
            return true;
        }
        
        if (in_array($name, $this->deleteTags)) {
            return false;
        }
        
        if (!preg_match("/^[a-z0-9]+$/i", $name)) {
            return false;
            /*if (preg_match("!(?:\@|://)!i", $name)) {
                return '&lt;' . $name . '&gt;';
                $this->_xhtml .= '&lt;' . $name . '&gt;';
            }
            return true;
            */
        }
        if (in_array(strtolower($name), $this->singleTags)) {
            return '<' . $name . $this->_writeAttrs($attrs) . '/>';
        }    
        return '<' . $name . $this->_writeAttrs($attrs) . '>';
        
    }
  
    /*
     * Main parsing fuction
     *
     * @param string $doc HTML document for processing
     * @return string Processed (X)HTML document
     * @access public
     */
    function parse($doc) 
    {

       // Save all '<' symbols
       //$doc = preg_replace("/<(?=[^a-zA-Z\/\!\?\%])/", '&lt;', $doc);

       // Web documents shouldn't contains \x00 symbol
       //$doc = str_replace("\x00", '', $doc);

       // Opera6 bug workaround
       //$doc = str_replace("\xC0\xBC", '&lt;', $doc);

       // UTF-7 encoding ASCII decode
       //$doc = $this->repackUTF7($doc);

        if (!extension_loaded('tidy')) {
            dl('tidy.so');
        }
//        print_r(strlen($doc));exit;
        // too large!!!?
        if (strlen($doc) > 1000000) {
            $doc = substr($doc, 0, 1000000);
        }
        $tree = tidy_parse_string($doc,array(),'UTF8');
        
//        print_r($tree);exit;
        
        return $this->tidyTree($tree->root());
       // use tidy!!!!
       
        

    }
    
    function parseFile($fn) 
    {

       // Save all '<' symbols
       //$doc = preg_replace("/<(?=[^a-zA-Z\/\!\?\%])/", '&lt;', $doc);

       // Web documents shouldn't contains \x00 symbol
       //$doc = str_replace("\x00", '', $doc);

       // Opera6 bug workaround
       //$doc = str_replace("\xC0\xBC", '&lt;', $doc);

       // UTF-7 encoding ASCII decode
       //$doc = $this->repackUTF7($doc);

        if (!extension_loaded('tidy')) {
            die("Add tidy extension to extension.ini");
        }
        $tree = tidy_parse_file($fn,array(),'UTF8');
        
        
        
        return $this->tidyTree($tree->root());
       // use tidy!!!!
       
        

    }
    
    function tidyTree($node) {
//         print_r($node);
        
        switch ($node->type) {
            case TIDY_NODETYPE_TEXT:
                if (strlen(trim($node->value))) {
                    $this->hasText = 1;
                }
                //echo htmlspecialchars($node->value);
                
                return $node->value;
            case TIDY_NODETYPE_STARTEND:
            case TIDY_NODETYPE_START:
                if (!empty($this->filter)) {
                    $this->filter->apply($node);
                }
                break;
            case TIDY_NODETYPE_END: // handled by start / singleTags..
                return;
                //$this->out .= "<". htmlspecialchars($node->name) .'/>';
                //return;
            
            case TIDY_NODETYPE_ROOT:
                break;
            default:
                return;
        }
        //echo $node->name ."\n";
        $add = '';
        $begin = '';
        $end = '';
        if ($node->type != TIDY_NODETYPE_ROOT) {
            //echo htmlspecialchars(print_r($node ,true));
            $add = $this->_openHandler($node->name, empty($node->attribute) ? array() : $node->attribute);
            if (is_string($add)) {
                $begin .= $add;
                if (!in_array(strtolower($node->name), $this->singleTags)) {
                    $cr = strtolower($node->name) == 'pre' ? '' : "\n";
                    $end = $cr . '</' . $node->name . '>';
                }
                 
            }
            if ($add === true) {
                return ''; // delete this tag and all the contents..
            }
        }
         
                // include children...
        if(!$node->hasChildren()){
            return $begin . $end;
        }
        foreach($node->child as $child){
           // echo "child of ". $node->name . ':' . $child->type . "\n";
            $begin .= $this->tidyTree($child);
        }
        return $begin . $end;
             
            
            
    }

    /**
     * UTF-7 decoding fuction
     *
     * @param string $str HTML document for recode ASCII part of UTF-7 back to ASCII
     * @return string Decoded document
     * @access private
     */
    function repackUTF7($str)
    {
       return preg_replace_callback('!\+([0-9a-zA-Z/]+)\-!', array($this, 'repackUTF7Callback'), $str);
    }

    /**
     * Additional UTF-7 decoding fuction
     *
     * @param string $str String for recode ASCII part of UTF-7 back to ASCII
     * @return string Recoded string
     * @access private
     */
    function repackUTF7Callback($str)
    {
       $str = base64_decode($str[1]);
       $str = preg_replace_callback('/^((?:\x00.)*)((?:[^\x00].)+)/', array($this, 'repackUTF7Back'), $str);
       return preg_replace('/\x00(.)/', '$1', $str);
    }

    /**
     * Additional UTF-7 encoding fuction
     *
     * @param string $str String for recode ASCII part of UTF-7 back to ASCII
     * @return string Recoded string
     * @access private
     */
    function repackUTF7Back($str)
    {
       return $str[1].'+'.rtrim(base64_encode($str[2]), '=').'-';
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

