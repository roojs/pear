<?php

require_once 'File/DXF/BasicObject.php';

class File_DXF_Subclass extends File_DXF_BasicObject
{

    public $subclassMarker; // 100

    function __construct($cfg=array()) 
	{
		$this->subclassMarker = str_replace("File_DXF_", "", get_class($this));
		parent::__construct($cfg=array());
	}

}