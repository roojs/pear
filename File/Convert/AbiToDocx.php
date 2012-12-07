<?php
/**
 * File Convert AbiWord To Docx

 */


class File_Convert_AbiToDocx 
{
        
	public function __construct($fn) 
        {
                $this->fileName = $fn;
                return;
                require_once dirname(__FILE__) .'/../Convert.php';
                $x = new File_Convert($fn, 'application/abiword' );
                $x->convert('application/abiword');
                $x->serve('attachment');
            
                exit;
                
        }
        
        function save($fn)
        {
                require_once __DIR__ . '/../../Document/Word/Writer.php';
                require_once __DIR__ . '/../../System.php';
               // $this->tmpdir  = System::mktemp("-d abitodocx");
                $this->tmpdir  = '/tmp';
                $this->lastNode = '';
                $this->style[] = array();
                $this->style['a'] = array('color'=>'0000FF', 'underline'=>'single'); // set default link style
                $this->sectionType = '';
                $this->headerText = '';
                $this->footerText = '';
                $this->writer = new Document_Word_Writer(); // New Word Document
                $this->section = $this->writer->createSection();
                $this->pass = 1;
                $this->parseAbi();
                $this->pass = 2;
                $this->parseAbi();
                $this->saveDocx( $fn ); // uses this->writer...
                
        }
        function parseAbi()
        {
                $this->xr = new XMLReader(); // New XML Reader

                if(!$this->xr->open($this->fileName)){
                    return PEAR::raiseError('Failed to open input file.');
                }
                
                while ($this->xr->read()){
                    if ($this->xr->nodeType == XMLReader::END_ELEMENT) {
                        continue;
                    }
                    $method = 'handle_'.$this->xr->name;
                    if (!method_exists($this, $method)) {
//                        echo "NOT HANLED {$this->xr->name} <br/>";
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
            $this->setNodeStyle($this->xr->getAttribute('name'), 'props');
            
        }
        
        function handle_table() 
        {
            if ($this->pass != 2) {
                return;
            }
            $this->setNodeStyle('table', 'props'); // Define table style
            $this->table = $this->section->addTable(); // Add table
        }
        
        function handle_cell()
        {
            if ($this->pass != 2) {
                return;
            }
            $this->setNodeStyle('cell', 'props'); // Define cell style
            if($this->style['cell']['colunmNum'] == 0){
                $height = '';
                if(array_key_exists('height' . $this->style['cell']['rowNum'], $this->style['table'])){
                    $height = $this->parseWH($this->style['table']['height' . $this->style['cell']['rowNum']],null);
                }
                $this->table->addRow($height);
            }
            $this->cellWidth = '';
            if(array_key_exists('width' . $this->style['cell']['colunmNum'], $this->style['table'])){
                $this->cellWidth = $this->parseWH($this->style['table']['width' . $this->style['cell']['colunmNum']],null);
            }
            $this->cell = $this->table->addCell($this->cellWidth, $this->style['cell']);
            $this->lastNode = 'cell';
        }
        
        function handle_p()
        {
            $this->setNodeStyle('p', 'props'); // Define p style
            if($this->xr->getAttribute('Style') == 'Normal'){
                $this->style['p'] = array_merge((array)$this->style['Normal'],(array)  $this->style['p']);
            }
            
            if ($this->pass != 2) {
                return;
            }
            
            $pObj = $this->xr->expand();
            $skipNode = array('a','image');
            foreach($pObj->childNodes as $node){
                if(in_array($node->nodeName, $skipNode)){
                    return;
                }
            }
            if($this->lastNode == 'cell'){
                $this->lastNode = '';
                $this->cell->addText($this->xr->readString(), $this->style['p']);
            }
            
        }
        
        function handle_a()
        {
            if ($this->pass != 2) {
                return;
            }
            if($this->xr->getAttribute('props')){
                $this->setNodeStyle('a', 'props'); // Define a style
            }
            $linkHref = $this->xr->getAttribute('xlink:href');
            $linkName = $this->xr->readString();
            if($this->lastNode == 'cell'){
                $this->lastNode = '';
                $this->cell->addLink($linkHref, $linkName,  $this->style['a']);
            }else{
                $this->section->addLink($linkHref, $linkName,  $this->style['a']);
            }
        }
        
        function handle_image()
        {
            if ($this->pass != 2) {
                return;
            }
            $this->setNodeStyle('image', 'props'); // Define image style
            $image = $this->xr->getAttribute('dataid');
            foreach($this->style['image'] as $key => $value){
                $this->style['image'][$key] = $this->parseWH($value,'image');
            }
            $this->section->addImage($this->tmpdir . '/' . $image . '.jpg', $this->style['image']);
            
        }
        
        function handle_pbr() 
        {
            if ($this->pass != 2) {
                return;
            }
            $this->section = $this->writer->createSection();
        }
        
        function handle_d()
        {
            if ($this->pass == 2) {
                return;
            }
            $this->sectionType = '';
            $data = base64_decode($this->xr->readString()); // Create the image source if not exist!
            $imageId = $this->xr->getAttribute('name');
            $path = $this->tmpdir . '/' . $imageId . '.jpg';
            if(!file_exists($path)){
               file_put_contents($path, $data); 
            }   
           
        }
        
        function handle_section()
        {
            if ($this->pass == 2) {
                return;
            }
            
            $sectionType = $this->xr->getAttribute('type');
            if($sectionType == 'header'){
                $this->sectionType = 'header';
                $this->header = $this->section->createHeader();
            }elseif($sectionType == 'footer'){
                $this->sectionType = 'footer';
                $this->footer = $this->section->createFooter();
            }
        }
        
        function handle_field()
        {
            if ($this->pass == 2) {
                return;
            }
            $fieldType = $this->xr->getAttribute('type');
            $this->setNodeStyle('field', 'props'); // Define field style
            $this->style['field'] = array_merge((array)$this->style['field'],(array)  $this->style['p']);
            if($fieldType == 'page_number'){
                $this->header->addPreserveText('{PAGE}', $this->style['field']);
            }
        }
        
        function handle_c()
        {
            if ($this->pass == 2) {
                return;
            }
            $this->setNodeStyle('c', 'props'); // Define header style
            $this->style['c'] = array_merge((array)$this->style['c'],(array)  $this->style['p']);
            if($this->sectionType == 'header')
            {
                if($this->xr->readString() != ''){
                    $this->header->addText($this->xr->readString() , $this->style['c']);
                }
            }elseif($this->sectionType == 'footer') {
                if($this->xr->readString() != ''){
                    $this->footer->addText($this->xr->readString() , $this->style['c']);
                }
            }
        }


        function parseWH($wh,$type=null)
        {
            $changeType = preg_replace('/[^a-z]/', '', $wh);
            $num = preg_replace('/[^0-9.]/', '', $wh);
            if($type == 'image'){
                if($changeType == 'in'){
                    return $num * 75;
                }else{
                    return $num;
                }
            }
            if($changeType == 'in'){
                return $num * 1435;
            }elseif($changeType == 'cm'){
                return $num * 567;
            }else{
                return $num;
            }
            
        }
      
        function parseTextBody($onto)
        {
            
        }
        
        public function setNodeStyle($node, $attrName)
        {
            $this->style[$node] = $this->parseProps($this->xr->getAttribute($attrName));
        }
        
        public function parseProps($attribute)
        {
            if(empty($attribute)){
                return;
            }
            $data = explode(';', $attribute);
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
                        $attrArray[trim($attr[0])] = $attr[1];
                }
            }
            return array_map('trim', $attrArray);
        }
        
//        public function inchToPx($num){
//            return $num * 75;
//        }
//         
        public function saveDocx($fn){
            require_once __DIR__ . '/../../Document/Word/Writer/IOFactory.php';
            $objWriter = Document_Word_Writer_IOFactory::createWriter($this->writer, 'Word2007');
            //$objWriter->save($this->tmpdir . '/' . $fn);
            //$objWriter->save($fn);
            $objWriter->save($fn);
            
        }
         
        
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