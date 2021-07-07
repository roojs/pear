<?php

require_once 'File/DXF/BasicObject.php';

class File_DXF_VIEW extends File_DXF_BasicObject
{
    public $name;

    /**
     * View constructor.
     * @param $name
     */
    function __construct($name)
    {
        $this->name = $name;
        parent::__construct();
    }

    public function getName()
    {
        return $this->name;
    }
}
