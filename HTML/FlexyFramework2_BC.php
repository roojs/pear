<?php
/* compatibility with old framework..
eventually we will have to do a HTML_FlexyFramework_FC to do it the other way round..

*/
     
class HTML_FlexyFramework {
    static function get() {
        return HTML_FlexyFramework2::get();
    }
}

class HTML_FlexyFramework_Page extends HTML_FlexyFramework2_Page {}