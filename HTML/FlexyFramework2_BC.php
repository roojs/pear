<?php
/* compatibility with old framework..
eventually we will have to do a HTML_FlexyFramework_FC to do it the other way round..

*/
     
class HTML_FlexyFramework {
    static function get() {
        return HTML_FlexyFramework2::get();
    }
    static function ensureSingle($sig, $class) {
        return HTML_FlexyFramework2::ensureSingle($sig,$class);
    }
    
    
}

class HTML_FlexyFramework_Page extends HTML_FlexyFramework2_Page {}
require_once 'PDO/DB_DataObject.php';
