<?php
class File_DXF_SectionObjects extends File_DXF_Section
{
    public function __construct()
    {
        parent::__construct('objects');
    }
    public function parse()
    {
        // TODO add the actually read objects
		require_once 'File/DXF/Dictionary.php';

		$this->addItem(new File_DXF_Dictionary(array('ACAD_GROUP')));
    }
}
