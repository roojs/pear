<?php
/**
 * File Convert AbiWord To Docx

 */


class File_Convert_AbiToDocx 
{
        private $_abiFileName;
        
	public function __construct($abiFileName) 
        {
                $this->_abiFIleName = $abiFileName;
                
                $xr = new XMLReader();
                if(!$xr->open($abiFileName)){
                    die("Failed to open input file.");
                }
                
                require_once __DIR__ . '/../../Document/Word/Writer.php';
                // New Word Document
                $PHPWord = new Document_Word_Writer();
                
                while ($xr->read()){
                    
                    if ($xr->nodeType == XMLReader::END_ELEMENT) {
                        continue;
                    }
                    
                    if($xr->name === 'table'){
                        // New portrait section
                        $section = $PHPWord->createSection();
                        // Draw Table
                        $this->drawTable($PHPWord, $section, $xr);
                        // Page Break
                        $section->addPageBreak();
                        
                    }
//                    elseif($xr->name === 'image'){
//                        // New portrait section
//                        $section = $PHPWord->createSection();
//                        
//                        $imageId = $xr->getAttribute('dataid');
//                        $props = $this->parseProps($xr->getAttribute('props'));
//                        
//                        $map[$imageId] = $section->addImageDefered('/tmp/'.$imageId.'.jpg');
//                        
//                    }
                    
//                    elseif($xr->name === 'd'){
//                        $data = base64_decode($xr->readString());
//                        $imageId = $xr->getAttribute('name');
//                        $path = '/tmp/' . $xr->getAttribute('name') . '.jpg';
//                        file_put_contents($path, $data);
//                        $section->addImageToCollection($map[$imageId], $path);
//                    }
                    
                }
                $xr->close();
                
                // Save File
                require_once __DIR__ . '/../../Document/Word/Writer/IOFactory.php';
                $objWriter = Document_Word_Writer_IOFactory::createWriter($PHPWord, 'Word2007');
                $objWriter->save('/tmp/AbiToDocx.docx');
	}
        
        public function drawTable(Document_Word_Writer $PHPWord, $section, $xr){
                // Define table style arrays
                $tableStyle = $this->parseProps($xr->getAttribute('props'));
                print_r($tableStyle);
                exit;
                // Add table style
                //$PHPWord->addTableStyle('myOwnTableStyle', $tableStyle, null);
                   
                // Add table
                $table = $section->addTable(); //'myOwnTableStyle');
                    
                $widths = explode('/', $tableStyle['table-column-props']);
                $heights = explode('/', $tableStyle['table-row-heights']);
                $tableObj = $xr->expand();

                foreach($tableObj->childNodes as $cellObj){
                    if($cellObj->nodeName === 'cell'){
                        $cellStyle = $this->parseProps($cellObj->getAttribute('props'));
                        if($cellStyle['left-attach'] == 0) {
                            $table->addRow();
                        }   
                        foreach($cellObj->childNodes as $pObj){
                            if($pObj->nodeName === 'p'){
                                $pStyle = $this->parseProps($pObj->getAttribute('style'));
                                $table->addCell(300, $cellStyle)->addText($pObj->nodeValue, $pStyle);
                            }
                        }
                    }
                 }
        }

        public function parseProps($attribute)
        {
            $data = explode(';', $attribute);
            if(count($data) == 1)
                return $attribute;
            $print_r($data);
            foreach ($data as $attrs){
                $attr = explode(':', $attrs);
                switch ($attr) {
                case 'table-column-props':
                    $props = explode('/', $attr);
                    foreach($props as $index => $prop){
                        $attrArray['width'.$index] = $prop;
                    }
                    break;
                case 'table-row-heights':
                    $props = explode('/', $attr);
                    foreach($props as $index => $prop){
                        $attrArray['height'.$index] = $prop;
                    }
                    break;
                }
            }
            return $attrArray;
            
        }
        
        public function getAbiFileName() 
        {
		return $this->_abiFileName;
	}
	
	
	public function setProperties($abiFileName) 
        {
		$this->_abiFileName = $abiFileName;
		return $this;
	}
    
}
?>