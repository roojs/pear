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
        $this->replaceDocBullets();
        $this->replaceAname();
    }

    function replaceDocBullets()
    {
        $doc = $this->node->ownerDocument;
        $xpath = new DOMXPath($doc);
        $node = $this->node;

        // replace the class of the nodes that have the class MsoListParagraphCxSpFirst, MsoListParagraphCxSpMiddle, MsoListParagraphCxSpLast, q1-indent-1 with MsoListParagraph
        $nodes = $xpath->query(
            "//*[
                contains(@class, 'MsoListParagraphCxSpFirst')
                or
                contains(@class, 'MsoListParagraphCxSpMiddle')
                or
                contains(@class, 'MsoListParagraphCxSpLast')
                or
                contains(@class, 'q1-indent-1')
            ]",
            $node
        );
        foreach($nodes as $n) {
            $n->setAttribute('class', 'MsoListParagraph');
        }

        // replace the class of the h2 that has the style mso-list with MsoListParagraph
        $nodes = $doc->getElementsByTagName('h2');
        foreach($nodes as $n) {
            $style = $n->getAttribute('style');

            // no attribute 'style'
            if(empty($style)) {
                continue;
            }

            if(preg_match('/mso-list:/', $style)) {
                $n->setAttribute('class', 'MsoListParagraph');
            }
        }

        // replace the class of the MsoNormal that has the style mso-list with MsoListParagraph, otherwise MsoNormalx
        $nodes = $doc->getElementsByTagName('MsoNormal');
        foreach($nodes as $n) {
            $style = $n->getAttribute('style');

            // no attribute 'style'
            if(empty($style)) {
                continue;
            }

            if(preg_match('/mso-list:/', $style)) {
                $n->setAttribute('class', 'MsoListParagraph');
            }
            else {
                $n->setAttribute('class', 'MsoNormalx');
            }
        }

        // process the MsoListParagraph nodes
        $nodes = $xpath->query(
            "//*[
                contains(@class, 'MsoListParagraph')
            ]",
            $node
        );
        foreach($nodes as $n) {
            $this->replaceDocBullet($doc, $xpath, $n);
        }
    }

    function replaceDocBullet($doc, $xpath, $node)
    {
        // removed already => skip
        if(empty($node->parentNode)) {
            return;
        }

        $items = array();
        $type = 'ul';
        $ns = $node;
        
        // read a list, it's type and it's items
        while($ns) {

            if($ns->nodeType != 1) {
                $ns = $ns->nextSibling;
                continue;
            }
            
            $class = $ns->getAttribute('class');

            // stop if not list paragraph
            if(empty($class) || !preg_match('/(MsoListParagraph|ql-indent-1)/i', $class)) {
                break;
            }

            $spans = $ns->getElementsByTagName('span');

            $styles = $this->styleToArray($ns);

            // paragraph with style 'mso-list'
            if(!empty($styles['mso-list'])){
                $items[] = $ns;
                $ns = $ns->nextSibling;
                if(!count($spans)) {
                    continue;
                }

                // get font family from span
                $fontFamily = '';
                foreach($spans as $span) {
                    $styles = $this->styleToArray($span);

                    foreach($styles as $k => $v) {
                        if(preg_match('/font-family/', $k)) {
                            $fontFamily = $v;
                        }
                    }

                    if(!empty($fontFamily)) {
                        break;
                    }
                }

                if(!empty($fontFamily) && !preg_match('/(Symbol|Wingdings)/', $fontFamily)) {
                    $type = 'ol';
                }
                continue;
            }

            // paragraph without style 'mso-list'
            if (!count($spans)) {
                break;
            }

            $has_list = false;
            foreach($spans as $span) {
                $styles = $this->styleToArray($span);
                // span with style 'mso-list'
                if(!empty($styles['mso-list'])){
                    $has_list = true;
                    break;
                }
            }

            if(!$has_list) {
                break;
            }

            $items[] = $ns;
            $ns = $ns->nextSibling;
        }

        if(!count($items)) {
            return;
        }
        
        // create elements for the list and the items

        $list = $doc->createElement($type);
        $node->parentNode->insertBefore($list, $node);

        $marginToDepth = array();
        $depth = 0;
        $lvl = 0;
        $stack = array($list);
        $lastLi = false;

        foreach($items as $item) {
            $spans = $item->getElementsByTagName('span');

            // no span => remove and skip
            if(!count($spans)) {
                $item->parentNode->removeChild($item);
                continue;
            }

            foreach($spans as $span) {
                $styles = $this->styleToArray($span);
                if(empty($styles['mso-list'])) {
                    continue;
                }

                // get starting number from span for ordered list
                if($type == 'ol') {
                    $num = preg_replace('/[^0-9]+/', '', $span->textContent);
                }
                break;
            }

            $styles = $this->styleToArray($item);

            if(empty($styles['mso-list'])) {
                $item->parentNode->removeChild($item);
                continue;
            }

            $margin = empty($styles['margin-left']) ? 0 : $styles['margin-left'];
            if(!isset($marginToDepth[$margin])) {
                $marginToDepth[$margin] = $depth;
                $depth ++;
            }

            // get nested level based on margin left
            $nlvl = $marginToDepth[$margin];

            if($nlvl > $lvl) {
                // new list for a new nested level
                $newList = $doc->createElement($type);
                if(!$lastLi) {
                    $stack[$lvl]->appendChild($doc->createElement('li'));   
                }
                $lastLi->appendChild($newList);
                $stack[$nlvl] = $newList;
            }
            $lvl = $nlvl;

            // set starting number for ordered list
            if(empty($stack[$nlvl]->getAttribute('start')) && $type == 'ol') {
                $stack[$nlvl]->setAttribute('start', $num);
            } 

            // add list item to the list
            $nli = $stack[$nlvl]->appendChild($doc->createElement('li'));
            $nli->nodeValue = $item->nodeValue;
            $lastLi = $nli;

            // remove the paragraph
            $item->parentNode->removeChild($item);
        }
    }

    function styleToArray($node)
    {
        $ret = array();
        $style = $node->getAttribute('style');
        if(empty($style)) {
            return $ret;
        }

        $styles = explode(';', $style);
        foreach($styles as $s) {
            if(!preg_match('/:/', $s)) {
                continue;
            }
            $kv = explode(':', $s);

            $ret[trim($kv[0])] = $kv[1];
        }

        return $ret;
    }

    function replaceAname()
    {
        $doc = $this->node->ownerDocument;

        $removeNodes = array();

        $nodes = $doc->getElementsByTagName('a');
        foreach($nodes as $n) {
            $name = $n->getAttribute('name');

            if(!empty($name)) {
                $n->removeAttribute('name');
            }

            $href = $n->getAttribute('href');

            if(!empty($href)) {
                continue;
            }

            $removeNodes[] = $n;
        }

        foreach($removeNodes as $n) {
            // copy of child nodes
            $childNodes = array();

            foreach($n->childNodes as $nn) {
                $childNodes[] = $nn;
            }

            foreach($childNodes as $nn) {
                $n->parentNode->insertBefore($nn, $n);
            }

            $n->parentNode->removeChild($n);
        }
    }

    function replaceImageTable($doc)
    {
        $nodes = $doc->getElementsByTagName('img');

        foreach($nodes as $n) {
            $td = $n->parentNode;
            if(empty($td) || strtoupper($td->nodeName) != 'TD') {
                return;
            }

            $tr = $td->parentNode;
            if(empty($tr) || strtoupper($tr->nodeName) != 'TR') {
                return;
            }

            $tbody = $tr->parentNode;
            if(empty($tbody) || strtoupper($tbody->nodeName) != 'TBODY') {
                return;
            }

            $table = $tbody->parentNode;
            if(empty($table) || strtoupper($table->nodeName) != 'TABLE') {
                return;
            }

            if(count($table->getElementsByTagName('tr')) != 2) {
                return;
            }

            if(count($table->getElementsByTagName('td')) != 3) {
                return;
            }

            if(trim($table->textContent) != '') {
                return;
            }

            $table->parentNode->insertBefore($n, $table);
            $table->parentNode->removeChild($table);
        }
    }
}
