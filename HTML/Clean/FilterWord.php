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
        parent::__construct($cfg);
        $this->replaceDocBullets($this->node);
        $this->replaceAname($this->node);
    }
    
    /**
     * Get elements by class name using XPath (since PHP DOMElement doesn't have getElementsByClassName)
     * 
     * @param DOMNode $node The node to search within
     * @param string $className The class name to search for
     * @return DOMNodeList List of matching elements (empty if node is invalid)
     */
    function getElementsByClassName($node, $className)
    {
        if (!$node) {
            // Return empty result by querying a non-existent element
            $dummyDoc = new DOMDocument();
            $xpath = new DOMXPath($dummyDoc);
            return $xpath->query('//*[@nonexistent]');
        }
        
        $doc = $node->ownerDocument ? $node->ownerDocument : ($node instanceof DOMDocument ? $node : null);
        if (!$doc) {
            $dummyDoc = new DOMDocument();
            $xpath = new DOMXPath($dummyDoc);
            return $xpath->query('//*[@nonexistent]');
        }
        
        $xpath = new DOMXPath($doc);
        // XPath to find elements with the class name (handles multiple classes)
        // Escape the className for XPath
        $escapedClassName = addslashes($className);
        $query = ".//*[contains(concat(' ', normalize-space(@class), ' '), ' {$escapedClassName} ')]";
        return $xpath->query($query, $node);
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
        var_dump($list);
        die('test');
        foreach($this->arrayFrom($list) as $l) {
            $l->setAttribute('class', "MsoListParagraph");
        }
    }
    
    
    function replaceDocBullets  ($doc)
    {
        // this is a bit odd - but it appears some indents use ql-indent-1
         //Roo.log(doc.innerHTML);
        
        $this->replaceClassList($this->getElementsByClassName($doc, 'MsoListParagraphCxSpFirst'));
        $this->replaceClassList($this->getElementsByClassName($doc, 'MsoListParagraphCxSpMiddle'));
        $this->replaceClassList($this->getElementsByClassName($doc, 'MsoListParagraphCxSpLast'));
        $this->replaceClassList($this->getElementsByClassName($doc, 'ql-indent-1'));

           
        // this is a bit hacky - we had one word document where h2 had a miso-list attribute.
        $htwo =  $this->arrayFrom($doc->getElementsByTagName('h2'));
        foreach($htwo as $a) {
            if ($a->hasAttribute('style') && preg_match('/mso-list:/', $a->getAttribute('style'))) {
                $a->setAttribute('class', "MsoListParagraph");
            }
        }
        $ar =  $this->arrayFrom($this->getElementsByClassName($doc, 'MsoNormal'));
        foreach($ar as $a) {
            if ($a->hasAttribute('style') && preg_match('/mso-list:/', $a->getAttribute('style'))) {
                $a->setAttribute('class', "MsoListParagraph");
            } else {
                $a->setAttribute('class', "MsoNormalx");
            }
        }
       
        $listpara = $this->getElementsByClassName($doc, 'MsoListParagraph');
        // Roo.log(doc.innerHTML);
        
        
        
        while($listpara->length) {
            
            $this->replaceDocBullet($listpara->item(0));
        }
      
    }
    
     
    
    function replaceDocBullet  ($p)
    {
        // gather all the siblings.
        $ns = $p;
        $parent = $p->parentNode;
        $doc = $parent->ownerDocument;
        $items = array();;
            
        $listtype = 'ul';   
        while ($ns) {
            if ($ns->nodeType != 1) {
                $ns = $ns->nextSibling;
                continue;
            }
            $cln = $ns->hasAttribute('class') ? $ns->getAttribute('class') : '';
            if (preg_match('/(MsoListParagraph|ql-indent-1)/i', $cln)) {
                break;
            }
            $spans = $ns->getElementsByTagName('span');
            if ($ns->hasAttribute('style') && preg_match('/mso-list/', $ns->getAttribute('style'))) {
                $items[] = $ns;
                $ns = $ns->nextSibling;
                $has_list = true;
                if ($spans->length && $spans->item(0)->hasAttribute('style')) {
                    $style = $this->styleToObject($spans->item(0), true);
                    if (!empty($style['font-family']) && !preg_match('/Symbol/', $style['font-family'])) {
                        $listtype = 'ol';
                    }
                }
                
                continue;
            }
            
            $spans = $ns->getElementsByTagName('span');
            if (!$spans->length) {
                break;
            }
            $has_list  = false;
            foreach($this->arrayFrom($spans) as $s) {
                if ($s->hasAttribute('style') &&  preg_match('/mso-list/', $s->getAttribute('style'))) {
                    $has_list = true;
                    break;
                }
            }
            if (!$has_list) {
                break;
            }
            $items[] = $ns;
            $ns = $ns->nextSibling;
            
            
        }
        if (!count($items)) {
            $ns->setAttribute('class', '');
            return;
        }
        
        $ul = $parent->ownerDocument->createElement($listtype); // what about number lists...
        $parent->insertBefore($ul, $p);
        $lvl = 0;
        $stack = array ( $ul );
        $last_li = false;
        
        $margin_to_depth = array();
        $max_margins = -1;
        
        foreach($items as $ipos => $n)
        {
        
            //Roo.log("got innertHMLT=" + n.innerHTML);
            
            $spans = $this->arrayFrom($n->getElementsByTagName('span'));
            if (!count($spans)) {
                //Roo.log("No spans found");
                 
                $parent->removeChild($n);
                
                
                continue; // skip it...
            }
           
                
            $num = 1;
            $style = array();
            foreach($spans as $i => $span) {
            
                $style = $this->styleToObject($span, true);
                if (empty($style['mso-list']) ) {
                    continue;
                }
                if ($listtype == 'ol') {
                   $num = preg_replace('/[^0-9]+]/g', '', $span->textContent)  * 1;
                }
                $span->parentNode->removeChild($span); // remove the fake bullet.
                break;
            }
            //Roo.log("NOW GOT innertHMLT=" + n.innerHTML);
            $style = $this->styleToObject($n, true); // mo-list is from the parent node.
            if (empty($style['mso-list'])) {
                  
                $parent->removeChild($n);
                 
                continue;
            }
            
            $margin = $style['margin-left'];
            if (empty($margin_to_depth[$margin]) ) {
                $max_margins++;
                $margin_to_depth[$margin] = $max_margins;
            }
            $nlvl = $margin_to_depth[$margin] ;
             
            if ($nlvl > $lvl) {
                //new indent
                $nul = $doc->createElement($listtype); // what about number lists...
                if (!$last_li) {
                    $last_li = $doc->createElement('li');
                    $stack[$lvl]->appendChild($last_li);
                }
                $last_li->appendChild($nul);
                $stack[$nlvl] = $nul;
                
            }
            $lvl = $nlvl;
            
            // not starting at 1..
            if (!$stack[$nlvl]->hasAttribute("start") && $listtype == "ol") {
                $stack[$nlvl]->setAttribute("start", $num);
            }
            
            $nli = $stack[$nlvl]->appendChild($doc->createElement('li'));
            $last_li = $nli;
            $this->copyInnerHtml($n, $nli);
            //$nli->innerHTML = $n->innerHTML;
            //Roo.log("innerHTML = " + n.innerHTML);
            $parent->removeChild($n);
            
              
        }
        
        
        
        
    }
    
    
    
}
