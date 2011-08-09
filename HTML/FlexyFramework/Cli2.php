<?php

/**
 *
 * convert $cli_opts array into values based on the
 * command line..
 *
 *
 * 
 *
 *
 */
class HTML_FlexyFramework_Cli2
{
   
    static function parseArgs($classname)
    {
        print_r($classname::$cli_opts);
        
    }
    
}
