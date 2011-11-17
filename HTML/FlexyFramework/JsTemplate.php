<?php


class HTML_FlexyFramework_JsTemplate {
    
    
    function HTML_FlexyFramework_JsTemplate($fn)
    {
        // cached? - check file see if we have cached contents.
        
        
        $contents = file_get_contents($fn);
        $ar = preg_split('/(\{[^\}]+})/', $contents, -1, PREG_SPLIT_DELIM_CAPTURE);
        echo '<PRE>' . htmlspecialchars(print_r($ar,true));
        
        $ret = array();
        
        $ret[] = "function(t) {\n    var ret=[];\n";
        
        foreach($ar as $item) {
            switch(true) {
                case (!strlen($item)):
                    continue;
                
                case ($item[0] != '{'):
                    $ret[] = "ret+= ". json_encode($item) . ";\n";
                    continue;
                
                case (substr($item,1,3) == 'if('):
                    $ret[] = substr($item,1,-1);
                    continue;
                
                case (substr($item,1,4) == 'end:'):
                    $ret[] = "}";
                    continue;
                
            }
            
            
        }
        echo '<PRE>' . htmlspecialchars(print_r($ret,true));
        
        
    }
    
    
    
}

// testing..
new HTML_FlexyFramework_JsTemplate('/home/alan/gitlive/web.mtrack/MTrackWeb/jtemplates/TimelineTicket.html');


