<?php


class HTML_FlexyFramework_DataObjects extends HTML_FlexyFramework_Page {
    
    function getAuth()
    {
        return true; // auth handled by framework..
    }
    
    function get()
    {
        require_once 'HTML/FlexyFramework/Generator.php';
        HTML_FlexyFramework_Generator::$generateClasses = true;
        DB_DataObject::debugLevel(1);
        // make sure the cache generator is on..
        HTML_FlexyFramework::get()->dataObjectsCache = true;
        $ff = HTML_FlexyFramework::get()->generateDataobjectsCache(true);
        die("Generation done..");
    }
    
    
}