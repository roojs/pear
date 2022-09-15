<?php



/**
 *  replaces bad attributes... and attribute values
 *  
 *  done by walking all elements
 *   
 *  
 *
 */ 

require_once 'Filter.php';

class HTML_Clean_FilterAttribute  extends HTML_Clean_Filter
{
   
    var $tag =  true; // all tags
    
    var $attrib_black = array(); // array
    var $attrib_clean = array(); // array
    var $attrib_white = array(); // array
    
    var $style_black = array(); // array
    var $style_white = array(); // array
    
    function __construct($cfg)
    {
        parent::__construct($cfg);
        $this->walk($cfg['node']);
    } 
    
     function replaceTag ($node)
    {
        if (!$node->hasAttributes()) {
            return true; // do children.
        }
        $ats = $this->arrayFrom($node->attributes);
        foreach($ats as $a) {
            
            // remove all if we have a white list..
            if (count($this->attrib_white) && array_search(strtolower($a->name), $this->attrib_white) !== false) {
                $node->removeAttribute($a->name);
                continue;
            }
            
            // always remove 'on'
            if (substr(strtolower($a->name),0,2) == 'on')  {
                $node->removeAttribute($a->name);
                continue;
            }
            
            
            if (array_search( strtolower($a->name),$this->attrib_black) !== false) {
                $node->removeAttribute($a->name);
                continue;
            }
            if (array_search( strtolower($a->name),$this->attrib_clean) !== false)  {
                $this->cleanAttr($node,$a->name,$a->value); // fixme..
                continue;
            }
                
            if ($a->name == 'style') {
                $this->cleanStyle($node,$a->name,$a->value);
                continue;
            }
            /// clean up MS crap..
            // tecnically this should be a list of valid class'es..
            
            
            if ($a->name == 'class') {
                if (preg_match('/^Mso/', $a->value)) {
                    $node->removeAttribute('class');
                    continue;
                }
                if (preg_match('/^body$/', $a->value)) {
                    $node->removeAttribute('class');
                    continue;
                }
            }
            
            
            // style cleanup!?
            // class cleanup?
            
        }
        return true; // clean children
    }
        
    cleanAttr: function(node, n,v)
    {
        
        if (v.match(/^\./) || v.match(/^\//)) {
            return;
        }
        if (v.match(/^(http|https):\/\//)
            || v.match(/^mailto:/) 
            || v.match(/^ftp:/)
            || v.match(/^data:/)
            ) {
            return;
        }
        if (v.match(/^#/)) {
            return;
        }
        if (v.match(/^\{/)) { // allow template editing.
            return;
        }
//            Roo.log("(REMOVE TAG)"+ node.tagName +'.' + n + '=' + v);
        node.removeAttribute(n);
        
    },
    cleanStyle : function(node,  n,v)
    {
        if (v.match(/expression/)) { //XSS?? should we even bother..
            node.removeAttribute(n);
            return;
        }
        
        var parts = v.split(/;/);
        var clean = [];
        
        Roo.each(parts, function(p) {
            p = p.replace(/^\s+/g,'').replace(/\s+$/g,'');
            if (!p.length) {
                return true;
            }
            var l = p.split(':').shift().replace(/\s+/g,'');
            l = l.replace(/^\s+/g,'').replace(/\s+$/g,'');
            
            if ( this.style_black.length && (this.style_black.indexOf(l) > -1 || this.style_black.indexOf(l.toLowerCase()) > -1)) {
                return true;
            }
            //Roo.log()
            // only allow 'c whitelisted system attributes'
            if ( this.style_white.length &&  style_white.indexOf(l) < 0 && style_white.indexOf(l.toLowerCase()) < 0 ) {
                return true;
            }
            
            
            clean.push(p);
            return true;
        },this);
        if (clean.length) { 
            node.setAttribute(n, clean.join(';'));
        } else {
            node.removeAttribute(n);
        }
        
    }
        