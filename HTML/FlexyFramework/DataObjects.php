<?php


class HTML_FlexyFramework_DataObjects extends HTML_FlexyFramework_Page
{
    /**
     * cli_options: the options for cli usage..
     * @see Console_Getargs_Options
     */
      /*
      
    static $cli_opts  = array(
        'test' => array(
            'short' => 't',
            'min' => 1,
            'max' => 1,
            //'default' => 0, -- no default if it is required..
            'desc' => 'A test argument that has to be set..'  
        ),
        
        
        
        
    );
      */
    
    function getAuth()
    {
        return true; // auth handled by framework..
    }
    
    function get()
    {
        require_once 'HTML/FlexyFramework/Generator.php';
         
        HTML_FlexyFramework_Generator::$generateClasses = true;
        $ff = HTML_FlexyFramework::get();

        $ff->DB_DataObject['debug'] = 1;
        $ff->debug = 1;
        if (empty($ff->dataObjectsCache)) {
            die("make sure dataObjectsCache in set to true the index file");
        }
        // make sure the cache generator is on..
        $ff->dataObjectsCache = true;
        $ff->generateDataobjectsCache(true);
        die("Generation done..");
    }
    
    
}