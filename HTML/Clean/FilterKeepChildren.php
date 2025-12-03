<?php

/**
 *
 * if the node matches, it will replace the child with children.
 * done for wierd namespaced nodes, and stuff like font.
 *
 * js one extends black?
 *
 */

 
require_once 'Filter.php';

class HTML_Clean_FilterKeepChildren extends HTML_Clean_Filter
{
    static $counter = 0;
    function __construct($cfg)
    {
        parent::__construct($cfg);
        if ($this->tag === false) { //not sure why.
            return;
        }
        
        $this->walk($this->node);
    } 
 
    function replaceTag ($n)
    {
        self::$counter++;
        var_dump(self::$counter);
        var_dump($n);
        if(self::$counter == 2) {
            die('test');
        }
        // $ar = $this->arrayFrom($n->childNodes);

        // // remove first.. - otherwise due to our walking method - the parent will not look at them.
        // foreach($ar as $t) {
        //     if (!$this->isTagMatch($t)) {
        //         continue;
        //     }
        //     $this->replaceTag($t); // this effetively walks all the children.
        // }

        $this->removeNodeKeepChildren($n);
        return false; // don't walk children
        
    }
    
     
    
}