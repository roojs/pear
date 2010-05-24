<?php


// an xml spreadsheet writer 
// take an xml file, and create an excel file from it.. (or maybe gnumeric..?)
// depreciated - current best practice is to use JS to generate gnumeric XML and run ssconvert..


class XML_Spreadsheet_Writer
{

    var $filename = "unknown.xls";
    var $workbook;
    var $worksheet; // active worksheet
    var $formats = array(); // the named formats
    var $worksheetNames = array();
    var $debugLevel = 0;
    /*
    * @static
    */
    function &load($string)
    {
        
        if (!extension_loaded('domxml')) {
            dl('domxml.'. PHP_SHLIB_SUFFIX);
        }
        $dom = domxml_open_mem($string);
        if (!$dom) { 
            echo '<code>';echo htmlspecialchars($string);exit;
        }
        $root = $dom->document_element();
        
        $writer = new XML_Spreadsheet_Writer;
        $writer->handle($root);
        return $writer;
    }
   
    
    function send() 
    {
        $this->workbook->send($this->filename);
        $this->workbook->close();
    }
    
    
    function handle($node) 
    {
        $this->debug($node->tagname);
        $method = 'handle'.$node->tagname;
        $this->debug( "SEND: $method : {$node->tagname}<BR>");
        $this->$method($node);
        if (!$node->child_nodes()) {
            return;
        }
        foreach($node->child_nodes() as $cnode) {
            if (is_a($cnode,'DomElement')) {
                $this->handle($cnode);
            }
        }
    }
    
    function handleWorkbook($node) 
    {
        require_once 'Spreadsheet/Excel/Writer.php';
        $this->workbook = new Spreadsheet_Excel_Writer();
        $this->filename = $node->get_attribute('filename');
    }
    
    function handleWorkSheet($node) 
    {
        if (isset($this->worksheet)) {
            unset($this->worksheet);
        }
        $this->worksheet = &$this->workbook->addWorksheet($node->get_attribute("name"));
        if (!is_a($this->worksheet,'PEAR_Error')) {
            $this->worksheetNames[] = $node->get_attribute("name");
        } else {
            $name = 'Page ' . count($this->worksheetNames);
            $this->worksheet = &$this->workbook->addWorksheet($name);
            $this->worksheetNames[] = $name;
        }
        
        if ($node->get_attribute("active") == "true") {
           // print_r($this->worksheet);
            $this->worksheet->activate();
            $this->worksheet->select();
        } 
    }
    function handleFormat($node) 
    {
        $ar = $this->_attributesToArray($node);
        $name = $ar['name'];
        unset($ar['name']);
        $this->formats[$name] = &$this->workbook->addFormat($ar);
    }
    
    function handleColumn($node) 
    {
        $ar = $this->_attributesToArray($node);
        $this->worksheet->setColumn (
             $this->_parseCol($ar['firstcol']),$this->_parseCol($ar['lastcol']), $ar['width'],
            isset($ar['format']) ? $this->formats[$ar['format']]  : 0,
            isset($ar['hidden']) ? 1 : 0
        );
    }
    function handleRow($node) 
    {
        $ar = $this->_attributesToArray($node);
        $this->worksheet->setRow (
            $ar['row'],$ar['height'],
            isset($ar['format']) ? $this->formats[$ar['format']]  : 0
        );
    }
    
    
    
    function handleCell($node) 
    {
        
        $ar = $this->_attributesToArray($node);
        $ar['type'] = isset($ar['type']) ? $ar['type'] :"String" ;
         
        switch($ar['type']) {
            case 'Number':
                if (isset($ar['format'])) {
                    $this->worksheet->writeNumber(
                        $ar['row'],
                        $this->_parseCol($ar['col']),
                        (float) $this->_innerText($node),
                        $this->formats[$ar['format']] 
                        );
                } else { 
                    $this->worksheet->writeNumber(
                        $ar['row'],
                        $this->_parseCol($ar['col']),
                        (float) $this->_innerText($node));
                }
                break;
            case 'Date':
                $date = $this->_excelDate($this->_innerText($node));
                if ($date != '') {
                    if (isset($ar['format'])) {
                        $this->worksheet->writeNumber(
                            $ar['row'],
                            $this->_parseCol($ar['col']),
                            (float) $date ,
                            $this->formats[$ar['format']]
                            );
                    } else { 
                        $this->worksheet->writeNumber(
                            $ar['row'],
                            $this->_parseCol($ar['col']),
                             (float)  $date 
                            );
                    }
                    break;
                }
            case 'String':
                if (isset($ar['format'])) {
                    $this->worksheet->writeString(
                        $ar['row'],
                        $this->_parseCol($ar['col']),
                        $this->_innerText($node),
                        $this->formats[$ar['format']]
                        );
                } else { 
                    $this->worksheet->writeString(
                        $ar['row'],
                        $this->_parseCol($ar['col']),
                        $this->_innerText($node)
                        );
                }
                break;
           
               
            case 'Formula':
                if (isset($ar['format'])) {
                    $this->worksheet->writeFormula(
                        $ar['row'],
                        $this->_parseCol($ar['col']),
                         $this->_innerText($node),
                        $this->formats[$ar['format']]
                        );
                } else { 
                    $this->worksheet->writeFormula(
                        $ar['row'],
                        $this->_parseCol($ar['col']),
                        $this->_innerText($node)
                        );
                }
                break;
            case 'Image':
                if (empty($ar['src'])) {
                    break;
                }
                // convert to bitmap!
                $fn = $this->_createBitmap($ar['src']);
                $info = getimagesize($fn);
                // is this needed?
                //$this->worksheet->writeString($ar['row'], 
                //    $this->_parseCol($ar['col']),
                //    "");
                    // kludge the y scale to work around bug.
                $this->worksheet->insertBitmap (
                    $ar['row'], 
                    $this->_parseCol($ar['col']), 
                    $fn,
                    0,0,1,0.3
                );
                unlink($fn);
                    
                    
                
        }    
    
    }
    
    function _parseCol($string) {
        if (preg_match('/^[0-9]+$/', $string)) {
            return (int) $string;
        }
        
        // assume it's A/B/C etc..
       // let's only handle A and AA
        $ret = 0;
        for($i=0;$i<strlen($string); $i++) {
            $ord = ord(substr($string, -($i+1), 1)) - ord("A");
            $ord = (($ord < 0) || ($ord > 26)) ? 0 : $ord;
            if ($i==0) {
                $ret += $ord;
                continue;
            }
            $ret += ((26^$i) * $ord);
        }
        $this->debug( "CONVERTED $string to $ret<BR>");
        return $ret;
    }
    
    function _attributesToArray($node) 
    {
        $ar = $node->attributes();
        $ret = array();
        foreach($ar as $anode) {
            $ret[$anode->name] = str_replace('&quot;','"',$anode->value);
        }
        $this->debug(print_r($ret,true));
        return $ret;
    }
    function _innerText($node) 
    {
        $ret = '';
        foreach($node->child_nodes() as $cnode) {
            if (is_a($cnode,'DomText')) {
                $ret .= $cnode->node_value();
            }
        }
        return $ret;
    }
    
    function _excelDate($iso) {
        if (!strlen(trim($iso))) {
            return '';
        }
        $bits = explode('-', $iso);
        require_once 'Date/Calc.php';
        //? should this be
        //print_r($bits);
        $r = Date_Calc::dateDiff(30,12,1899,$bits[2],$bits[1],$bits[0]);
        //echo "$iso => $r";exit;
        return $r;
    }
    
    function _createBitmap($src) 
    {
        // depends on imagick!
        if (!extension_loaded('imagick')) {
            dl('imagick.so');
        }
        // we litter files a bit here.. - we should do a clean up really..
        $srcTmp = ini_get('session.save_path') . '/' .uniqid('xsw_').'_'.basename($src);
        $data = file_get_contents($src);
        $fh = fopen($srcTmp,'w');
        fwrite($fh, $data);
        fclose($fh);
       
        $handle = imagick_readimage($srcTmp) ;
        $ret = ini_get('session.save_path') . '/' .uniqid('xsw_') .'_'.basename($src).'.bmp';
        imagick_writeimage( $handle, $ret );
         unlink($srcTmp);
        return $ret;
    }
    
    function debug($str) {
        if ($this->debugLevel > 0) {
            //trigger_error(__CLASS__."::DEBUG\n$str", E_USER_WARNING);
            echo __CLASS__."::DEBUG\n$str<BR>";
        }
    }
}