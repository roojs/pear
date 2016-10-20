<?php

/* the actual text container..

does a quick bit of parsing to see if it a {template}var ..
*/ 

class XML_SvgToPDF_Tspan extends XML_SvgToPDF_Base { 

    var $content = ''; // applies to tspan only..
    var $x = false;
    var $y = false;
    var $args = array(); // arguments..
    function fromXmlNode($node) {
        parent::fromXmlNode($node);
        $this->x = false;
        $this->y = false;
        $this->content = $node->textContent;
        /*
        if (isset($this->x)) {
               unset($this->x); 
        }
        if (isset($this->y)) {
               unset($this->y); 
        }
        */
        static $trans = false;
        if (!$trans) {
            $trans = array_flip(get_html_translation_table(HTML_ENTITIES));
        }
        
        if (strlen($this->content)) {
            // convert &amp; etc. 
            if (strpos($this->content,'&') !== false) {
                $this->content = strtr($this->content, $trans);
                $this->content = str_replace('&apos;',"'",$this->content);

                $this->content =  preg_replace_callback('/&#(\d+);/m', array($this, 'content_replace'),
                                    $this->content);
            }
            if (!empty($node->language)) {
                // todo - other conversions....
                $this->content = mb_convert_encoding($this->content,'BIG-5','UTF-8');

            }
            // dynamic parts..
            if (false === strpos($this->content,'{')) {
                return;
            }
            preg_match_all('/\{([a-z0-9_.]+(\(\))?)\}/i',$this->content,$matches);
 
            $this->args = $matches[1];
            foreach($this->args as $v) {
                $this->content  = str_replace('{'.$v.'}', '%s',$this->content);
            }
            //$this->content = preg_replace('/\{('.implode('|',$matches[1]).')\}/','%s',$this->content);
        }
        
    
    }
    
    function content_replace($matches) { // php5.2 needs this to be a function... 
            return chr($matches[1]);
    }
    
    
    function shift() // disable shifting on text
    {
        return;
    }
    function transform() 
    {
        
    }
   



}
