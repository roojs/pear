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
                
                $this->generateImages();
                
                $xr = new XMLReader();
                if(!$xr->open($abiFileName)){
                    die("Failed to open input file.");
                }
                
                require_once __DIR__ . '/../../Document/Word/Writer.php';
                // New Word Document
                $PHPWord = new Document_Word_Writer();
                
                $section = $PHPWord->createSection();
                
                while ($xr->read()){
                    
                    if ($xr->nodeType == XMLReader::END_ELEMENT) {
                        continue;
                    }
                    
                    if($xr->name === 'table'){
                        // Draw Table
                        $this->drawTable($section, $xr);
                        // Page Break
                        $section->addPageBreak();
                        
                    }elseif($xr->name === 'image'){
                        $this->drawImage($section,$xr);
                        
                    }
                    
                }
                $xr->close();
                
                // Save File
                require_once __DIR__ . '/../../Document/Word/Writer/IOFactory.php';
                $objWriter = Document_Word_Writer_IOFactory::createWriter($PHPWord, 'Word2007');
                $objWriter->save('/tmp/AbiToDocx.docx');
	}
        
        public function drawTable($section, $xr){
                // Define table style arrays
                $tableStyle = $this->parseProps($xr->getAttribute('props'));
                // Add table
                $table = $section->addTable();
                
                $tableObj = $xr->expand();

                foreach($tableObj->childNodes as $cellObj){
                    if($cellObj->nodeName === 'cell'){
                        $cellStyle = $this->parseProps($cellObj->getAttribute('props'));
                        if($cellStyle['colunmNum'] == 0) {
                            $height = array_key_exists('height'.$cellStyle['rowNum'], $tableStyle) ? $tableStyle['height'.$cellStyle['rowNum']] : '';
                            $height = preg_replace('/[^0-9.]/', '', $height);
                            $table->addRow($this->inchToPx($height));
                        }   
                        
                        foreach($cellObj->childNodes as $pObj){
                            if($pObj->nodeName === 'p'){
                                $pStyle = $this->parseProps($pObj->getAttribute('style'));
                                $width = array_key_exists('width'.$cellStyle['colunmNum'], $tableStyle) ? $tableStyle['width'.$cellStyle['colunmNum']] : '';
                                $width = preg_replace('/[^0-9.]/', '', $width);
                                $text = iconv(mb_detect_encoding($pObj->nodeValue), "UTF-8", $pObj->nodeValue);
                                $table->addCell($this->inchToPx($width), $cellStyle)->addText($pObj->nodeValue, $pStyle);
                            }
                        }
                    }
                 }
        }
        
        public function drawImage($section, $xr){
            $imageId = $xr->getAttribute('dataid');
            $path = '/tmp/'.$imageId.'.jpg';
            if(file_exists($path)){
                $imageStyle = $this->parseProps($xr->getAttribute('props'));
                $width = preg_replace('/[^0-9.]/', '', $imageStyle['width']);
                $height = preg_replace('/[^0-9.]/', '', $imageStyle['height']);
                $section->addImage($path, array('width'=>$this->inchToPx($width), 'height'=>$this->inchToPx($height), 'align'=>'center'));
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
                    case 'height':
                        $props = explode('/', $attr[1]);
                        foreach($props as $prop){
                            $attrArray['height'] = $prop;
                        }
                        break;
                    case 'width':
                        $props = explode('/', $attr[1]);
                        foreach($props as $prop){
                            $attrArray['width'] = $prop;
                        }
                        break;
                }
            }
            return $attrArray;
            
        }
        
        public function generateImages(){
            $xr = new XMLReader();
            if(!$xr->open($this->_abiFIleName)){
                die("Failed to open input file.");
            }

            //create the image source if not exist!
            while ($xr->read()){
                if ($xr->nodeType == XMLReader::END_ELEMENT) {
                    continue;
                }
                if($xr->name === 'd'){
                    $data = base64_decode($xr->readString());
                    $imageId = $xr->getAttribute('name');
                    $path = '/tmp/' . $xr->getAttribute('name') . '.jpg';
                    if(!file_exists($path)){
                       file_put_contents($path, $data); 
                    }
                }

            }
            $xr->close();
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
        
        public function inchToPx($num){
            return $num * 75;
        }
    
}
?>