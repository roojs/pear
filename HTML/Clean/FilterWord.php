<?php

/**
 * Does a few very specific word paste filtering - doc bullents an a name= tags.
 * 
 * doesnt really use filter parent much
 *
 */ 

require_once 'Filter.php';

class HTML_Clean_FilterWord extends HTML_Clean_Filter
{
    
    var $tag = true;
    
    function __construct($cfg)
    {
        $this->replaceDocBullets($cfg->node);
        $this->replaceAname($cfg->node);
    }
   
    
    function replaceAname  ($doc)
    {
        // replace all the a/name without..
        $aa= $this->arrayFrom($doc->getElementsByTagName('a'));
        
        for ($i = 0; $i  < count($aa); $i++) {
            $a = $aa[$i];
            if ($a->hasAttribute("name")) {
                $a->removeAttribute("name");
            }
            if ($a->hasAttribute("href")) {
                continue;
            }
            // reparent children.
            $this->removeNodeKeepChildren($a);
            
        }
        
        
        
    }
    function replaceClassList($list)
    {
        foreach($this->arrayFrom($list) as $l) {
            $l->setAttribute('class', "MsoListParagraph");
        }
    }
    
    function replaceDocBullets  ($doc)
    {
        // this is a bit odd - but it appears some indents use ql-indent-1
         //Roo.log(doc.innerHTML);
        
        $this->replaceClassList($doc->getElementsByClassName('MsoListParagraphCxSpFirst'));
        $this->replaceClassList($doc->getElementsByClassName('MsoListParagraphCxSpMiddle'));
        $this->replaceClassList($doc->getElementsByClassName('MsoListParagraphCxSpLast'));
        $this->replaceClassList($doc->getElementsByClassName('ql-indent-1'));

           
        // this is a bit hacky - we had one word document where h2 had a miso-list attribute.
        $htwo =  $this->arrayFrom($doc->getElementsByTagName('h2'));
        foreach($htow as $a) {
            if ($a->hasAttribute('style') && preg_match('/mso-list:/', $a->getAttribute('style'))) {
                $a->setAttribute('class', "MsoListParagraph");
            }
        }
        $ar =  $this->arrayFrom($doc->getElementsByClassName('MsoNormal'));
        foreach($ar as $a) {
            if ($a->hasAttribute('style') && preg_match('/mso-list:/', $a->getAttribute('style'))) {
                $a->setAttribute('class', "MsoListParagraph");
            } else {
                $a->setAttribute('class', "MsoNormalx");
            }
        }
       
        $listpara = $doc->getElementsByClassName('MsoListParagraph');
        // Roo.log(doc.innerHTML);
        
        
        
        while($listpara->length) {
            
            $this->replaceDocBullet($listpara->item(0));
        }
      
    }
    
     
    
    replaceDocBullet : function(p)
    {
        // gather all the siblings.
        var ns = p,
            parent = p.parentNode,
            doc = parent.ownerDocument,
            items = [];
            
        var listtype = 'ul';   
        while (ns) {
            if (ns.nodeType != 1) {
                ns = ns.nextSibling;
                continue;
            }
            if (!ns.className.match(/(MsoListParagraph|ql-indent-1)/i)) {
                break;
            }
            var spans = ns.getElementsByTagName('span');
            if (ns.hasAttribute('style') && ns.getAttribute('style').match(/mso-list/)) {
                items.push(ns);
                ns = ns.nextSibling;
                has_list = true;
                if (spans.length && spans[0].hasAttribute('style')) {
                    var  style = this.styleToObject(spans[0]);
                    if (typeof(style['font-family']) != 'undefined' && !style['font-family'].match(/Symbol/)) {
                        listtype = 'ol';
                    }
                }
                
                continue;
            }
            var spans = ns.getElementsByTagName('span');
            if (!spans.length) {
                break;
            }
            var has_list  = false;
            for(var i = 0; i < spans.length; i++) {
                if (spans[i].hasAttribute('style') && spans[i].getAttribute('style').match(/mso-list/)) {
                    has_list = true;
                    break;
                }
            }
            if (!has_list) {
                break;
            }
            items.push(ns);
            ns = ns.nextSibling;
            
            
        }
        if (!items.length) {
            ns.className = "";
            return;
        }
        
        var ul = parent.ownerDocument.createElement(listtype); // what about number lists...
        parent.insertBefore(ul, p);
        var lvl = 0;
        var stack = [ ul ];
        var last_li = false;
        
        var margin_to_depth = {};
        max_margins = -1;
        
        items.forEach(function(n, ipos) {
            //Roo.log("got innertHMLT=" + n.innerHTML);
            
            var spans = n.getElementsByTagName('span');
            if (!spans.length) {
                //Roo.log("No spans found");
                 
                parent.removeChild(n);
                
                
                return; // skip it...
            }
           
                
            var num = 1;
            var style = {};
            for(var i = 0; i < spans.length; i++) {
            
                style = this.styleToObject(spans[i]);
                if (typeof(style['mso-list']) == 'undefined') {
                    continue;
                }
                if (listtype == 'ol') {
                   num = spans[i].innerText.replace(/[^0-9]+]/g,'')  * 1;
                }
                spans[i].parentNode.removeChild(spans[i]); // remove the fake bullet.
                break;
            }
            //Roo.log("NOW GOT innertHMLT=" + n.innerHTML);
            style = this.styleToObject(n); // mo-list is from the parent node.
            if (typeof(style['mso-list']) == 'undefined') {
                //Roo.log("parent is missing level");
                  
                parent.removeChild(n);
                 
                return;
            }
            
            var margin = style['margin-left'];
            if (typeof(margin_to_depth[margin]) == 'undefined') {
                max_margins++;
                margin_to_depth[margin] = max_margins;
            }
            nlvl = margin_to_depth[margin] ;
             
            if (nlvl > lvl) {
                //new indent
                var nul = doc.createElement(listtype); // what about number lists...
                if (!last_li) {
                    last_li = doc.createElement('li');
                    stack[lvl].appendChild(last_li);
                }
                last_li.appendChild(nul);
                stack[nlvl] = nul;
                
            }
            lvl = nlvl;
            
            // not starting at 1..
            if (!stack[nlvl].hasAttribute("start") && listtype == "ol") {
                stack[nlvl].setAttribute("start", num);
            }
            
            var nli = stack[nlvl].appendChild(doc.createElement('li'));
            last_li = nli;
            nli.innerHTML = n.innerHTML;
            //Roo.log("innerHTML = " + n.innerHTML);
            parent.removeChild(n);
            
             
             
            
        },this);
        
        
        
        
    }
    
    
    
}
