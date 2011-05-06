<?php


class HTML_FlexyFramework_Cli
{
    var $available = array(
        'HTML/FlexyFramework/Database'
    )
    function help($cli)
    {
             echo "
    $cli DataObjects

    Run Basic DataObjects Generator (used to generate plain HTML_FlexyFramework projects)
        
";
    }
    
}
