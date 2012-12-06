<?php
/**
 * File Convert AbiWord To Docx

 */


class File_Convert_AbiToDocx 
{
        
	public function __construct() 
        {
                
                
        }
        
        function save($fn)
        {
                $this->fileName = $fn;
                require_once __DIR__ . '/../../Document/Word/Writer.php';
                require_once __DIR__ . '/../../System.php';
                $this->tmpdir  = System::mktemp("-d abitodocx");
                echo 'tmpdir' . $this->tmpdir; 
                
                // New Word Document
                $this->writer = new Document_Word_Writer();
                $this->pass = 1;
                $this->parseAbi();
               
//                $this->saveDocx( $fn ); // uses this->writer...
                
                
        }
        function parseAbi()
        {
                // New XML Reader
                $this->xr = new XMLReader();

                if(!$this->xr->open($this->fileName)){
                    return PEAR::raiseError('Failed to open input file.');
                }
                
                while ($this->xr->read()){
                    if ($this->xr->nodeType == XMLReader::END_ELEMENT) {
                        continue;
                    }
                    $method = 'handle_'.$this->xr->name;
                    if (!method_exists($this, $method)) {
                        echo "NOT HANLED {$this->xr->name} <br/>";
                    }else{
                        $this->$method();   
                    }
                }
        }
        
        function handle_s() 
        {
            if ($this->pass != 2) {
                return;
            }
            $styleName = $xr->getAttribute('name');
            $this->styleName = $this->parseProps($this->xr->getAttribute('props'));
            print_r($this->styleName);
            
        }
        
        function handle_Table() 
        {
            if ($this->pass != 2) {
                return;
            }
            
        }
        
        function handle_pbr() 
        {
//            $this->section = $PHPWord->createSection();
        }
        
        function parseAbiDom($node)
        {
            
            
        }
        
        function handle_d()
        {
            if ($this->pass == 2) {
                return;
            }
            
            $xr = new XMLReader();
            if(!$xr->open($this->fileName)){
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
            
            $this->pass = 2;
            $this->parseAbi();
        }
//                    // Handle All The Elements
//                    if($xr->name === 'table'){
//                        //New Section
//                        $section = $PHPWord->createSection();
//                        // Draw Table
//                        $this->drawTable($section, $xr);
//                    }elseif($xr->name === 'image'){
//                        //New Section
//                        $section = $PHPWord->createSection();
//                        // Draw Image
//                        $this->drawImage($section,$xr);
//                    }
//                }
//                // Close XML Reader
//                $xr->close();
//                // Save File
//                $this->saveDocx($PHPWord);
//	}
//        
        
        
//        public function drawTable($section, $xr){
//                // Define table style arrays
//                $tableStyle = $this->parseProps($xr->getAttribute('props'));
//                // Add table
//                $table = $section->addTable();
//                // Convert xr Element to DOM Object
//                $tableObj = $xr->expand();
//                // Draw The Table
//                foreach($tableObj->childNodes as $cellObj){
//                    if($cellObj->nodeName === 'cell'){
//                        $cellStyle = $this->parseProps($cellObj->getAttribute('props'));
//                        if($cellStyle['colunmNum'] == 0) {
//                            $height = array_key_exists('height'.$cellStyle['rowNum'], $tableStyle) ? $tableStyle['height'.$cellStyle['rowNum']] : '';
//                            $height = preg_replace('/[^0-9.]/', '', $height);
//                            $table->addRow($this->inchToPx($height));
//                        }   
//                        
//                        foreach($cellObj->childNodes as $pObj){
//                            if($pObj->nodeName === 'p'){
//                                $pStyle = $this->parseProps($pObj->getAttribute('style'));
//                                $width = array_key_exists('width'.$cellStyle['colunmNum'], $tableStyle) ? $tableStyle['width'.$cellStyle['colunmNum']] : '';
//                                $width = preg_replace('/[^0-9.]/', '', $width);
//                                $table->addCell($this->inchToPx($width), $cellStyle)->addText($pObj->nodeValue, $pStyle);
//                            }
//                        }
//                    }
//                 }
//        }
        function parseTextBody($onto)
        {
            
        }
        
        
//        public function drawImage($section, $xr){
//            // Get The Name of image
//            $imageId = $xr->getAttribute('dataid');
//            $path = '/tmp/'.$imageId.'.jpg';
//            if(file_exists($path)){
//                $imageStyle = $this->parseProps($xr->getAttribute('props'));
//                $width = preg_replace('/[^0-9.]/', '', $imageStyle['width']);
//                $height = preg_replace('/[^0-9.]/', '', $imageStyle['height']);
//                $section->addImage($path, array('width'=>$this->inchToPx($width), 'height'=>$this->inchToPx($height), 'align'=>'center'));
//            }
//        }
        
        public function parseProps($attribute)
        {
            $data = explode(';', $attribute);
            if(count($data) == 1){
                return $attribute;
            }
            return $this->getAttrDetail($data);
        }
        
        public function getAttrDetail($data)
        {
            foreach ($data as $attrs){
                $attr = explode(':', $attrs);
                switch (trim($attr[0])){
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
                    default :
                        $attrArray[trim($attr[0])] = $prop;
                }
            }
            return $attrArray;
        }
        
//        public function inchToPx($num){
//            return $num * 75;
//        }
//         
//        public function saveDocx(Document_Word_Writer $PHPWord){
//            require_once __DIR__ . '/../../Document/Word/Writer/IOFactory.php';
//            $objWriter = Document_Word_Writer_IOFactory::createWriter($PHPWord, 'Word2007');
//            $objWriter->save('/tmp/AbiToDocx.docx');
//        }
//        
//        public function getAbiFileName() 
//        {
//		return $this->_abiFileName;
//	}
//	
//	public function setProperties($abiFileName) 
//        {
//		$this->_abiFileName = $abiFileName;
//		return $this;
//	}    
}
?>