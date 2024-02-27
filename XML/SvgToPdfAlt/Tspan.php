<?php

/* the actual text container..

does a quick bit of parsing to see if it a {template}var ..
*/ 

class XML_SvgToPDFAlt_Tspan extends XML_SvgToPDFAlt_Base { 

    function fromNode($node) {
        parent::fromNode($node);
        if (isset($this->x)) {
               unset($this->x); 
        }
        if (isset($this->y)) {
               unset($this->y); 
        }
        static $trans = false;
        if (!$trans) {
            $trans = array_flip(get_html_translation_table(HTML_ENTITIES));
        }
        if (@$this->content) {
            if (strpos($this->content,'&') !== false) {
                $this->content = strtr($this->content, $trans);
                $this->content = str_replace('&apos;',"'",$this->content);

                $this->content =  preg_replace_callback(
                        '/&#(\d+);/m',
                            function($m) {
                            
                                return mb_chr($m[1]);
                            }  ,$this->content);
            }
            if (@$node->language) {
                // todo - other conversions....

                //$this->content = mb_convert_encoding($this->content,'BIG-5','UTF-8');

                
            }

            if (false === strpos($this->content,'{')) {
                return;
            }
            preg_match_all('/\{([a-z0-9_.]+(\(\))?)\}/i',$this->content,$matches);
//echo "<PRE>";            print_r($matches);
            //if (false !== strpos($this->content,'(')) {
                
            //    echo "<PRE>";print_R($matches);
            //    exit;
            //}
            
            $this->args = $matches[1];
            foreach($this->args as $v) {
                $this->content  = str_replace('{'.$v.'}', '%s',$this->content);
            }
            //$this->content = preg_replace('/\{('.implode('|',$matches[1]).')\}/','%s',$this->content);
        }
        
    }
            



}
