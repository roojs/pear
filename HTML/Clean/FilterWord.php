<?php

require_once 'Filter.php';

class HTML_Clean_FilterWord extends HTML_Clean_Filter
{
    
    function __construct($cfg)
    {
        $this->replaceDocBullets($cfg->node);
        $this->replaceAname($cfg->node);
    }
    
}
