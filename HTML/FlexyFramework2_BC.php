<?php
/* compatibility with old framework.. */
     
class HTML_FlexyFramework {
    static function get() {
        return HTML_FlexyFramework2::get();
    }
}

class HTML_FlexyFramework_Page extends HTML_FlexyFramework2_Page {}