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
                
                // Add table
                $table = $section->addTable();
                
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
            
            if(count($data) == 1){
                return $attribute;
            }
            
            foreach ($data as $attrs){
                $attr = explode(':', $attrs);
                
                switch (trim($attr[0])) {
                    case 'table-column-props':
                        $props = explode('/', $attr[1]);

                        foreach($props as $index => $prop){
                            $attrArray['width'.$index] = $prop;
                        }

                        break;
                    case 'table-row-heights':
                        $props = explode('/', $attr[1]);
                        foreach($props as $index => $prop){
                            $attrArray['height'.$index] = $prop;
                        }
                        break;
                    case 'left-attach':
                        $props = explode('/', $attr[1]);
                        foreach($props as $prop){
                            $attrArray['colunmNum'] = $prop;
                        }
                        break;
                    case 'top-attach':
                        $props = explode('/', $attr[1]);
                        foreach($props as $prop){
                            $attrArray['rowNum'] = $prop;
                        }
                        break;
                    case 'top-color':
                        $props = explode('/', $attr[1]);
                        foreach($props as $prop){
                            $attrArray['borderTopColor'] = $prop;
                        }
                        break;
                    case 'left-color':
                        $props = explode('/', $attr[1]);
                        foreach($props as $prop){
                            $attrArray['borderLeftColor'] = $prop;
                        }
                        break;
                    case 'right-color':
                        $props = explode('/', $attr[1]);
                        foreach($props as $prop){
                            $attrArray['borderRightColor'] = $prop;
                        }
                        break;
                    case 'bot-color':
                        $props = explode('/', $attr[1]);
                        foreach($props as $prop){
                            $attrArray['borderBottomColor'] = $prop;
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