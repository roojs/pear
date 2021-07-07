<?php
class File_DXF_SectionObjects extends File_DXF_Section
{

    public function __construct($cfg=array())
    {
        $cfg['name'] = 'objects'
        parent::__construct($cfg);
    }
    
    /**
	 *
	 * TODO ENHANCE / CHECK THE CODE BLOEW
	 *
	 */
	 
    public function parse()
    {
        // TODO add the actually read objects
		require_once 'File/DXF/Dictionary.php';

		$this->addItem(new File_DXF_Dictionary(array('ACAD_GROUP')));
    }
}
