<?php


HTML_FlexyFramework_JsTemplate {
    
    
    function HTML_FlexyFramework_JsTemplate($fn)
    {
        // cached? - check file see if we have cached contents.
        
        
        $contents = file_get_contents($fn);
        preg_split('/\{[^\}]+}', $contents);
        
        
        
    }
    
    
    
}