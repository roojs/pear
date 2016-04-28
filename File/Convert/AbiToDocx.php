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
                'text-align' => 'align',
//                'color' => 'color', // might be fgcolor
        );
    
        function save($fn)
        {
                require_once __DIR__ . '/../../Document/Word/Writer.php';
                require_once __DIR__ . '/../../System.php';
                $this->tmpdir  = System::mktemp("-d abitodocx");
                //$this->tmpdir  = '/tmp';
                $this->link = '';
                $this->style[] = array();
                $this->keepSection = false;
                $this->writer = new Document_Word_Writer(); // New Word Document
                $this->section = $this->writer->createSection();
                $this->pass = 1;
                $this->parseAbi();
                $this->pass = 2;
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
            ini_set('memory_limit', '512M');
            $this->xr = new XMLReader(); // New XML Reader

            if(!$this->xr->open($this->fileName)){
                return PEAR::raiseError('Failed to open input file.');
            }
            $state = array();
            $sections = array();
            $stack = array();
            
            // this may produce warnings... - &gt; confuses the XML reader.. (as we are using it in abiword generation.)
            
            while ($this->xr->read()){
                //$this->dumpsections($sections);
                 // echo $this->xr->name . '::' . count($sections). "<br/>"; 
                 $method = 'handle_'.$this->xr->name;
                 
                 
                 if ($this->xr->nodeType == XMLReader::END_ELEMENT) {
                     if($this->xr->name == 'section'){
                         $this->keepSection = false;
                     }
                     
                     if (method_exists($this, $method)) {
                        $this->style = array_pop($state);
                        $this->section = array_pop($sections);
                        array_pop($stack);
                       // echo "AFTER POP:"; $this->dumpsections($sections); 
                     }
                     continue;
                }
                
                $textNode = array('p','c','a');
//                    print_r($this->xr->name);
                
                if ($this->xr->name == '#text' && count($stack) &&  $this->pass==2 && in_array($stack[count($stack)-1], $textNode)) {
                    print_R($this->xr->name);echo "\n";
                    // the reader does not clean out the htmlizums...
                    $text =   $this->xr->value;
                    print_R($text);echo "\n";
                    print_R($this->style);echo "\n";
                    if(strpos($text, '{#PAGE#}') !== false || strpos($text, '{#NUMPAGES#}') !== false){
                        $this->section->addPreserveText(str_replace("#", "", $text), $this->style,$this->style);
                        
                    }elseif(is_array($this->style) && array_key_exists('href', $this->style)) {
                        $this->section->addLink($this->style['href'], $text,  $this->style);
                       
                    }else{
                        $this->section->addText($text, $this->style);
                    }
                    continue;
                }
                
                
                if ($this->xr->nodeType != XMLReader::ELEMENT) {
                    continue;
                }
               
                if (!method_exists($this, $method)) {
                        continue;
//                        echo "NOT HANLED {$this->xr->name} <br/>";
                } 
                
                if (!$this->xr->isEmptyElement) {
                   $stack[] = $this->xr->name;
                   $sections[] = $this->section;
                   $state[] = $this->style;
                }
                
                $this->$method();  
               
            }
        }
        
        function handle_s() 
        {
            if ($this->pass != 2) {
                return;
            }
            $this->writer->addFontStyle($this->xr->getAttribute('name'), $this->parseProps(),$this->parseProps());
        }
        
        function handle_table() 
        {
            if ($this->pass != 2) {
                return;
            }
            $this->style =  $this->parseProps();
//            print_r($this->style);
            $this->section = $this->section->addTable($this->style); // Add table
            
        }
        
        function handle_cell()
        {
            if ($this->pass != 2) {
                return;
            }
            $style =  $this->parseProps();
             
            if($style['columnNum'] == 0){
                $height = '';
                if(array_key_exists('height' . $style['rowNum'], $this->style)){
                    $height = $this->converttoDax($this->style['height' . $style['rowNum']],null);
                }
                $this->section->addRow($height);
            }
            $cellWidth = '';
            if (isset($this->style['width' . $style['columnNum'] ])) {
                $cellWidth = $this->converttoDax($this->style['width' . $style['columnNum']],null);
            }
            
            //echo "CW? " . $cellWidth . "|";
            $this->section = $this->section->addCell($cellWidth, $style);
         }
        
         
         
        function handle_p()
        {
            if ($this->pass != 2) {
                return;
            }
            
            $style =  $this->parseProps();
            if(!empty($style)){
                $this->style = array_merge($style, Document_Word_Writer_Style::getStyles());
            }else{
                $this->style = $this->xr->getAttribute('style');
            }
            
            if($this->keepSection){
                return;
            }
            // p must create a text run.. otherwise cells do not work..
             $this->section = $this->section->createTextRun($this->style);
            
        }
        
        function handle_a()
        {
            if ($this->pass != 2) {
                return;
            }
            $this->style = $this->parseProps();
            $this->style['href'] = $this->xr->getAttribute('xlink:href');
            
        }
        
        function handle_image()
        {
            if ($this->pass != 2) {
                return;
            }
            $style =  $this->parseProps();
            
            $image = $this->xr->getAttribute('dataid');
            foreach($style as $key => $value){
                $style[$key] = $this->converttoDax($value,'image');
            }
            if(!empty($image)){
                $this->section->addImage($this->tmpdir . '/' . $image . '.jpg', $style);
                return;
            }
            
            $path = $this->xr->getAttribute('filesrc');
            $this->section->addImage($path, $style);
            
//            echo '<PRE>';print_r($this->section);exit;
        }
        
        function handle_pbr() 
        {
            if ($this->pass != 2) {
                return;
            }
            $this->section->addPageBreak();
        }
        
        function handle_br() 
        {
            if ($this->pass != 2) {
                return;
            }
            $this->section->addTextBreak();
            //echo '<PRE>';print_r($this->section);exit;
        }
        
        function handle_section()
        {
            if ($this->pass != 2) {
                return;
            }
            // only made on first pass..
            
            $sectionType = $this->xr->getAttribute('type');
            if($sectionType == 'header'){
                $this->keepSection = true;
                $this->section = $this->section->createHeader();
            }elseif($sectionType == 'footer'){
                $this->keepSection = true;
                $this->section = $this->section->createFooter();
            }
        }
        
        function handle_field()
        {
            if ($this->pass != 2) {
                return;
            }
            //return; /// this would not work!
        
            $fieldType = $this->xr->getAttribute('type');
            // addPreserveText -- Only available in header / footer. See chapter "footer" or "header".
            if($fieldType == 'page_number' || $fieldType == 'number_pages'){
                $this->section->addPreserveText('Page {PAGE} of {NUMPAGES}', $this->style,$this->style);
            }
        }
        
        function handle_c()
        {
            if(is_array($this->style) && array_key_exists('href', $this->style)){
                $this->style = array_merge($this->style , $this->parseProps());
                return;
            }
            $this->style =  $this->parseProps();
//            $this->section->createTextRun($this->style);
        }
        
        /**
         * handle the image data 
         * 
         * not in used, we dont encore the image to base 64 any more
         */
        function handle_d()
        {
            if ($this->pass == 2) {
                return;
            }
            $data = base64_decode($this->xr->readString()); // Create the image source if not exist!
            $imageId = $this->xr->getAttribute('name');
            $path = $this->tmpdir . '/' . $imageId . '.jpg';
            if(!file_exists($path)){
               file_put_contents($path, $data); 
            }   
           
        }
        
        // converts inches / cm into dax (word measurments)
        function converttoDax($wh,$type=null)
        {
            $changeType = preg_replace('/[^a-z]/', '', $wh);
            $num = preg_replace('/[^0-9.]/', '', $wh);
            if($type == 'image'){
                if($changeType == 'in'){
                    return floor($num * 75);
                }
                return floor($num);
                
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
            ini_set('memory_limit', '512M');
            $attribute = $this->xr->getAttribute('props');
            if(empty($attribute)){
                return array();
            }
            $data = explode(';', trim($attribute));
        
            foreach ($data as $attrs){
                
                $attr = explode(':', trim($attrs));
                if (empty($attr[0])) {
                    continue;
                }
                
                switch (trim($attr[0])){
                    case 'table-width-fixed':
                        $attrArray['fixed'] = trim($attr[1]);
                        break;
                    
                    case 'table-column-props':
                        $props = explode('/', $attr[1]);
                        foreach($props as $index => $prop){
                            $attrArray['width'.$index] = trim($prop);
                            $attrArray['width'.$index.'_dax'] = $this->converttoDax(trim($prop),null);
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
                            $attrArray['columnNum'] = trim($prop);
                        }
                        break;
                    
                    case 'top-attach':
                        $props = explode('/', $attr[1]);
                        foreach($props as $prop){
                            $attrArray['rowNum'] = trim($prop);
                        }
                        break;
                        
                    case 'right-attach':
                        $props = explode('/', $attr[1]);
                        foreach($props as $prop){
                            $attrArray['mergeto'] = trim($prop);
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
                    // background color
                    case 'background-color':
                        $props = explode('/', $attr[1]);
                        foreach($props as $prop){
                            $attrArray['bgColor'] = trim($prop);
                        }
                        break;
                        
                    case 'font-weight':
                            $attrArray['bold'] = ($attr[1] == 'bold') ? trim($attr[1]) : false;
                            
                        break;
                    case 'font-style':
                            $attrArray['italic'] = ($attr[1] == 'italic') ? true : false;
                        break;
//                    
//                    case 'width':
//                        $props = explode('/', $attr[1]);
//                        foreach($props as $index => $prop){
//                            $attrArray['width'.$index] = trim($prop);
//                        }
//                        break;
//                        
                    default :
                        $key = trim($attr[0]);
                        if (empty($attr[1])) {
                            // bit annoying.. but we currently only convert from trustes sources, so do not need to handle this. 
                            PEAR::staticRaiseError("value missing in style key={$attr[0]}  for prop={$attribute}",0,PEAR_ERROR_DIE);
                        }
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
            // fill this in so it can handle landscape
            if ($this->pass == 1) {
                return;
            }
            
            $this->section->getSettings()->setSettingValue('_orientation', $this->xr->getAttribute('orientation'));
            $this->style['pageSizeW'] = $this->xr->getAttribute('width');
            $this->style['pageSizeH'] = $this->xr->getAttribute('height');
            
            return;
        }
        
        
}
 