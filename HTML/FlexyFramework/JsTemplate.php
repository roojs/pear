<?php


class HTML_FlexyFramework_JsTemplate {
    
    
    function HTML_FlexyFramework_JsTemplate($fn)
    {
        // cached? - check file see if we have cached contents.
        
        
        $contents = file_get_contents($fn);
        $ar = preg_split('/(\{[^\}]+})/', $contents, -1, PREG_SPLIT_DELIM_CAPTURE);
        echo '<PRE>' . htmlspecialchars(print_r($ar,true));
        
        $ret = array();
        
        $ret[] = "var ??? = function(t) {\n    var ret=[];\n";
        $indent = 1;
        foreach($ar as $item) {
            $in = str_repeat("    ", $indent);
            
            //var_Dump(substr($item,-3,2));
            switch(true) {
                case (!strlen($item)):
                    continue;
                
                case ($item[0] != '{'):
                    if (!strlen(trim($item))) {
                        continue;
                    }
                    $ret[] = $in . "ret+= ". json_encode($item) . ";\n";
                    continue;
                
                case (substr($item,1,3) == 'if('):
                    $ret[] = $in . substr($item,1,-1) . ' {';
                    $indent++;
                    continue;
                
                case (substr($item,1,4) == 'end:'):
                    $indent--;
                    $in = str_repeat("    ", $indent);
                    $ret[] = $in . "}";
                    continue;
                
                case (substr($item,1,7) == 'return:'):
                    $ret[] = $in . "return;";
                    continue;
                
                case (substr($item,1,9) == 'function:'):
                    $indent++;
                    $ret[] = $in . "function " . substr($item,10,-1) . '{';
                    continue;
                
                default:
                    if (substr($item,-3,2) == ':h') {
                        $ret[] = $in . "ret += ".  substr($item,1,-3) . ';';
                        continue;
                    }
                    $ret[] = $in . "ret += Roo.util.Format.htmlEncode(".  substr($item,1,-1).');';
                    continue;
                
            }
            
            
        }
        $in = str_repeat("    ", $indent);
        $ret[] = $in .  "return ret.join('');\n}\n";
        
        echo '<PRE>' . htmlspecialchars(implode("\n",$ret));
        
        
        
    }
    
    
    
}

// testing..
new HTML_FlexyFramework_JsTemplate('/home/alan/gitlive/web.mtrack/MTrackWeb/jtemplates/TimelineTicket.html');


