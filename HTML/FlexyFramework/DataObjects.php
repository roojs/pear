<?php


class HTML_FlexyFramework_DataObjects extends HTML_FlexyFramework_Page {
    
    function getAuth()
    {
        return true; // auth handled by framework..
    }
    
    function get()
    {
        require_once 'HTML/FlexyFramework/Generator.php';
        $generator = new HTML_FlexyFramework_Generator();
        $generator->generateClasses = true;
        $generator->start();
        die("dataobject genertor");
    }
    
    
}