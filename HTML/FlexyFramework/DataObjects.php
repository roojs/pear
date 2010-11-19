<?php


class HTML_FlexyFramework_DataObjects extends HTML_FlexyFramework_Page {
    
    function getAuth()
    {
        return true; // auth handled by framework..
    }
    
    function get()
    {
        require_once 'HTML/FlexyFramework/Generator.php';
        DB_DataObject::debugLevel(1);
        $generator = new HTML_FlexyFramework_Generator();
        HTML_FlexyFramework_Generator::$generateClasses = true;
        $generator->start();
        die("Generation done..");
    }
    
    
}