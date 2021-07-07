<?php

require_once 'File/DXF/BasicObject.php';

class File_DXF_SystemVariable extends File_DXF_BasicObject
{
    public $variable;
    public $values;
    
    public function getName()
    {
        return $this->variable;
    }
    
    /**
	 *
	 * TODO ENHANCE / CHECK THE CODE BLOEW
	 *
	 */

    public function getValues()
    {
        return $this->values;
    }
}
