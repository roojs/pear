<?php
/**
 * File Convert AbiWord To Docx

 */


class File_Convert_AbiToDocx 
{
    
    function __construct($fn) 
    {
            $this->fileName = $fn;
        //    echo file_get_contents($fn);exit;
            
            return;
            
            require_once dirname(__FILE__) .'/../Convert.php';
            $x = new File_Convert($fn, 'application/abiword' );
            $x->convert('application/abiword');
            $x->serve('attachment');
        
            exit;
            
    }
        // maps abiword css names to word style attributes
        var $styleMatch = array(
                'font-family' => 'name',
                'font-size' => 'size',
                'text-align' => 'align'
        );
    
        function save($fn)
        {
                require_once __DIR__ . '/../../Document/Word/Writer.php';
                require_once __DIR__ . '/../../System.php';
                //$this->tmpdir  = System::mktemp("-d abitodocx");
                $this->tmpdir  = '/tmp';
                $this->lastNode = '';
                $this->style[] = array();
                $this->metadata[] = array();
                $this->style['a'] = array('color'=>'0000FF', 'underline'=>'single'); // set default link style
                $this->headerText = '';
                $this->footerText = '';
                $this->writer = new Document_Word_Writer(); // New Word Document
                $this->section = $this->writer->createSection();
                $this->pass = 1;
                $this->parseAbi();
                $this->pass = 2;
                //echo "---parse 2--<br/>";
                $this->parseAbi();
                $this->saveDocx( $fn ); // uses this->writer...
                
        }
        function dumpsections($s)
        {
            foreach($s as $ss) echo get_class($ss) . ":";
            echo ":TOP". get_class($this->section);
            echo '<br/>';
        }
        
         
        
        
        function parseAbi()
        {
                $this->xr = new XMLReader(); // New XML Reader

                if(!$this->xr->open($this->fileName)){
                    return PEAR::raiseError('Failed to open input file.');
                }
                $state = array();
                $sections = array();
                $stack = array();
                while ($this->xr->read()){
                    //echo $this->xr->name . '::' . count($sections). "<br/>"; 
                     $method = 'handle_'.$this->xr->name;
                     
                     
                     if ($this->xr->nodeType == XMLReader::END_ELEMENT) {
                        if (method_exists($this, $method)) {
                            $this->style = array_pop($state);
                            $this->section = array_pop($sections);
                            array_pop($stack);
                           // echo "AFTER POP:"; $this->dumpsections($sections); 
                         }
                        continue;
                    }
                    
                    if ($this->xr->name == '#text' && count($stack)  && $stack[count($stack)-1] == 'p' && $this->pass==2) {
                        
                        $this->section->addText($this->xr->value);
                        continue;
                    }
                    
                    
                    if ($this->xr->nodeType != XMLReader::ELEMENT) {
                        continue;
                    }
                   
                    if (!method_exists($this, $method)) {
                            continue;
//                        echo "NOT HANLED {$this->xr->name} <br/>";
                    } 
                    //echo implode('..', $stack). ':' .$this->xr->name.'<BR/>';
                    //$this->dumpsections($sections);
                    
                    if (!$this->xr->isEmptyElement) {
                       $stack[] = $this->xr->name;
                       $sections[] = $this->section;
                       $state[] = $this->style;
                    }
                    $this->$method();  
                    
                    //if($this->xr->isEmptyElement){
                        //echo $method.'<Br/>';
                        
                    //        continue;
                    //}
                    
                   
                }
        }
        
        function handle_s() 
        {
            if ($this->pass == 2) {
                return;
            }
            $this->style[$this->xr->getAttribute('name')] =  $this->parseProps();
            
        }
        
        function handle_table() 
        {
            if ($this->pass != 2) {
                return;
            }
            $this->style['table'] =  $this->parseProps();
            
            $this->section = $this->section->addTable(); // Add table
        }
        
        function handle_cell()
        {
            if ($this->pass != 2) {
                return;
            }
            $this->style['cell'] =  $this->parseProps();
             
            if($this->style['cell']['colunmNum'] == 0){
                $height = '';
                if(array_key_exists('height' . $this->style['cell']['rowNum'], $this->style['table'])){
                    $height = $this->converttoDax($this->style['table']['height' . $this->style['cell']['rowNum']],null);
                }
                $this->section->addRow($height);
            }
            $cellWidth = '';
            if (isset($this->style['table']['width' . $this->style['cell']['colunmNum'] ])) {
                $cellWidth = $this->converttoDax($this->style['table']['width' . $this->style['cell']['colunmNum']],null);
            }
            //echo "CW? " . $cellWidth . "|";
            $this->section = $this->section->addCell($cellWidth, $this->style['cell']);
         }
        
        function handle_p()
        {
            $this->style['p'] =  $this->parseProps();
            
            if($this->xr->getAttribute('style') == 'Normal'){
                $this->style['p'] = array_merge((array)$this->style['Normal'],(array)  $this->style['p']);
            }
            
            if ($this->pass != 2) {
                return;
            }
            // second pass..
//            $pObj = $this->xr->expand();
//            $skipNode = array('a','image');
//            foreach($pObj->childNodes as $node){
//                if(in_array($node->nodeName, $skipNode)){
//                    return;
//                }
//            }
            //?? 
            $this->section = $this->section->createTextRun($this->style['p']);
            /*
            if($this->lastNode == 'cell'){
                $this->lastNode = '';
                
                
                
                $this->cell->addText($this->xr->readString(), $this->style['p']);
            }
            */
             
            
        }
        
        function handle_a()
        {
            if ($this->pass != 2) {
                return;
            }
            $this->style['a'] =  $this->parseProps();
             
            $linkHref = $this->xr->getAttribute('xlink:href');
            $linkName =  $this->xr->readString();
            $this->style['a'] = array_merge((array)$this->style['a'],(array)  $this->style['p']);
            
            $this->section->addLink($linkHref, $linkName,  $this->style['a']);
            
        }
        
        function handle_image()
        {
            if ($this->pass != 2) {
                return;
            }
            $this->style['image'] =  $this->parseProps();
            
            $image = $this->xr->getAttribute('dataid');
            foreach($this->style['image'] as $key => $value){
                $this->style['image'][$key] = $this->converttoDax($value,'image');
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
            // only made on first pass..
            
            $sectionType = $this->xr->getAttribute('type');
            if($sectionType == 'header'){
                $this->section = $this->section->createHeader();
            }elseif($sectionType == 'footer'){
                $this->section = $this->section->createFooter();
            }
        }
        
        function handle_field()
        {
            if ($this->pass == 2) {
                return;
            }
            return; /// this would not work!
        
            $fieldType = $this->xr->getAttribute('type');
            $this->style['field'] =  $this->parseProps();
            
            $this->style['field'] = array_merge((array)$this->style['field'],(array)  $this->style['p']);
            if($fieldType == 'page_number'){
                 
                $this->section->addPreserveText('{PAGE}', $this->style['field'],$this->style['field']);

                 
                
            }
            if($fieldType == 'number_pages'){
               
                    $this->header->addPreserveText('{NUMPAGES}', $this->style['field'],$this->style['field']);
                  
                
            }
        }
        
        function handle_c()
        {
            
            $this->style['c'] =  $this->parseProps();
            
            $this->style['c'] = array_merge( $this->style['c'],  $this->style['p']);
            
            if ($this->pass == 2) {
                 $str = $this->xr->readString();
                 $this->section->addText($str, $this->style['c']);
                 return;
            }
            // only handles on first pass...??
            // and it adds to header or footer?
            
           
            $str = $this->xr->readString();
            $str = str_replace(array('{#','#}'), array('{', '}'), $str);
            if (strlen($str)) {
                // fixme - kludge as parse does not subparse <fields>
                $this->section->addPreserveText($str , $this->style['c'],$this->style['c']);
            }
         
        }
        // converts inches / cm into dax (word measurments)
        function converttoDax($wh,$type=null)
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
        
      
        function parseProps()
        {
            
            $attribute = $this->xr->getAttribute('props');
            if(empty($attribute)){
                return array();
            }
            $data = explode(';', $attribute);
        
            foreach ($data as $attrs){
                
                $attr = explode(':', $attrs);
                
                switch (trim($attr[0])){
                    
                    case 'table-column-props':
                        $props = explode('/', $attr[1]);
                        foreach($props as $index => $prop){
                            $attrArray['width'.$index] = trim($prop);
                        }
                        break;
                    
                    case 'table-row-heights':
                        $props = explode('/', $attr[1]);
                        foreach($props as $index => $prop){
                            $attrArray['height'.$index] = trim($prop);
                        }
                        break;
                    
                    case 'left-attach':
                        $props = explode('/', $attr[1]);
                        foreach($props as $prop){
                            $attrArray['colunmNum'] = trim($prop);
                        }
                        break;
                    
                    case 'top-attach':
                        $props = explode('/', $attr[1]);
                        foreach($props as $prop){
                            $attrArray['rowNum'] = trim($prop);
                        }
                        break;
                    
                    case 'top-color':
                        $props = explode('/', $attr[1]);
                        foreach($props as $prop){
                            $attrArray['borderTopColor'] = trim($prop);
                        }
                        break;
                    
                    case 'left-color':
                        $props = explode('/', $attr[1]);
                        foreach($props as $prop){
                            $attrArray['borderLeftColor'] = trim($prop);
                        }
                        break;
                    
                    case 'right-color':
                        $props = explode('/', $attr[1]);
                        foreach($props as $prop){
                            $attrArray['borderRightColor'] = trim($prop);
                        }
                        break;
                    
                    case 'bot-color':
                        $props = explode('/', $attr[1]);
                        foreach($props as $prop){
                            $attrArray['borderBottomColor'] = trim($prop);
                        }
                        break;
                        
                    default :
                        $key = trim($attr[0]);
                        $value = trim($attr[1]);
                        if(array_key_exists($key, $this->styleMatch)){
                            $attrArray[$this->styleMatch[$key]] = $value;
                        }else{
                            $attrArray[$key] = $value;
                        }
                }
            }
            return  $attrArray;
        }
 
        function saveDocx($fn){
            require_once __DIR__ . '/../../Document/Word/Writer/IOFactory.php';
            $objWriter = Document_Word_Writer_IOFactory::createWriter($this->writer, 'Word2007');
            $objWriter->save($fn);
            
        }
        
        // NOT Useful Mothed
        
        function handle_metadata()
        {
            return;
        }
        
        function handle_m()
        {
            return;
        }
        
        function handle_rdf()
        {
            return;
        }
        
        function handle_history()
        {
            return;
        }
        
        function handle_styles()
        {
            return;
        }
        
        function handle_version()
        {
            return;
        }
        
        function handle_pagesize()
        {
            return;
        }
        
        
}
?>