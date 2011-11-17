<?php


class HTML_FlexyFramework_JsTemplate {
    
    
    function HTML_FlexyFramework_JsTemplate($fn)
    {
        // cached? - check file see if we have cached contents.
        
        
        $contents = file_get_contents($fn);
        $ar = preg_split('/\{[^\}]+}', $contents, -1, PREG_SPLIT_DELIM_CAPTURE);
        echo '<PRE>'; print_r($ar);
        
        
        
    }
    
    
    
}

// testing..
new HTML_FlexyFramework_JsTemplate('/home/alan/gitlive/web.mtrack/MTrackWeb/jtemplates/TimelineTicket.html');


