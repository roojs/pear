<?php


class XML_SvgToPDF_Text  extends XML_SvgToPDF_Base { 

    function fromXmlNode($node) {
        
        parent::fromXmlNode($node);
        
        // any text ???
        if (empty($this->children) || empty($this->children[0]->content)) {
            return;
        }
        
        // modify the alignment of the if this block content of the first child is "=="
        
        if (substr($this->children[0]->content,0,2) == '==') {
            $this->style['text-anchor'] = 'justify';
            $this->children[0]->content = substr($this->children[0]->content,2);
        }
    }
      
    function transform() {
        
        parent::transform();
        if (empty($this->transform)) {
            return; 
        }
        if (preg_match('/scale\(([0-9e.-]+),([0-9e.-]+)\)/',$this->transform,$args)) {
            $xscale = $args[1]; // do we use this??? = what do do about 'e'?
            $yscale = $args[2];
            $this->style['font-size'] *= $args[1];
        }
    }
    
        
    
    function writePDF($pdf,$data) {
        // set up font.. 
         
        $font = strtolower($this->style['font-family']);
        $font = trim($font, "'");
        
        static $map = array(
            'times new roman' => 'times',
            'courier new' => 'courier',
            'arial' => 'arial',
        );
        if (isset($map[$font])) {
            $font = $map[$font];
        } else {
    	    $font = 'times';
        }
        $ffont = $font;
        if (preg_match('/big5/i',$this->style['font-family'])) {
            $ffont = 'ARIALUNI';
            $font = 'arial'; // default if not big4
        }
            
        
        
        $weight =  '';
        if ($this->style['font-weight'] == 'bold') {
            $weight .=  'B';
        }
        if (@$this->style['font-style'] == 'italic') {
            $weight .=  'I';
        }
        
        if (@$this->style['text-decoration'] == 'underline') {
            $weight .=  'U';
        }
        // size..
        $size = $this->style['font-size']  * 0.85;
        
        $pdf->setFont($font,$weight,$size);
     
        switch(@$this->style['text-anchor']) {
            case 'end':
                $align = 'E';
                break;
            case 'middle':
                $align = 'M';
                break;
            case 'justify':
                $align = 'J';
                $max = 0;
                foreach ($this->children as $child) {
                    if (!@$child->content) {
                        continue;
                    }
                    $l = $pdf->getStringWidth($child->content);
                    $max = ($l > $max) ? $l : $max;
                }
                // fudge factor!
                $this->justifyLen = $max;
                break;
            default:
                $align = 'L';
            
        }
        
        $f = $this->toColor(@$this->style['fill']);
        if ($f) {
            $pdf->setTextColor($f[0],$f[1],$f[2]);
        }
     
     
     
        $yoffset = 0;
        $x =  $this->x   + @$this->xx;
        $y =  $this->y  + @$this->yy;
        if (empty($this->children)) {
            return;
        }
        $lineno = 0;
        foreach($this->children as $i=>$c) {
        
            $xx = $c->x !== false ? $c->x + @$this->xx : $x;
            $yy = $c->y !== false ? $c->y + @$this->yy : $y + ($lineno * $size * 1.3);
            $lineno++;              
            $val = $c->content;
            if ($ffont == 'ARIALUNI') { //) && preg_match('/[\x7f-\xff]+/',$val)) {
                $pdf->setFont('ARIALUNI' ,
                            $weight,
                            $size);
            }
            if (isset($c->args)) {
                
                $args = array();
                foreach($c->args as $v) {
                    if ($v == 'page') {
                        $args[] = $pdf->PageNo();
                        continue;
                    }
                    if ($v == 'nb') {
                        $args[] = '{nb}';
                        continue;
                    }
                    
                    // echo "GET: $v : '$val'<BR>";
                    $args[] = trim($this->getValue($data,trim($v))); // removes trailing CRLF...
                }
                
                
                $has_template = preg_match('/%s/', $val);
                     
                $val = trim(vsprintf($val,$args));
                
                if ($has_template && ($ffont == 'ARIALUNI') && preg_match('/[\x7f-\xff]+/',$val)) {
                    //require_once  'Text/ZhDetect.php';
                    //$detect = new Text_zhDetect;
                    //$type = $detect->guess($val);
                    //if ($v == 'S') {
                       
                    //    $val = @iconv('utf8', 'GB2312//IGNORE', $val);
                    //    $pdf->setFont('GB' ,
                    //        $weight,
                    //        $size);
                    //} else {
//                        $val = @iconv('utf8', 'BIG5//IGNORE', $val);
                        $pdf->setFont('ARIALUNI' ,
                            $weight,
                            $size);
                   //}
                }  else {
                    $val = @iconv('utf8','ascii//ignore',$val);
                }
                
                
                
                
            }
            $talign = $align;
            if ((!@$this->children[$i+1] ||  !strlen(trim(@$this->children[$i+1]->content))) && ($align == 'J')) {
                $talign = 'L';
            }
            
            $yoffset += $this->multiLine($pdf, str_replace("\r", "", explode("\n",$val)),
                    $xx/ 3.543307,
                    ($yy / 3.543307) + $yoffset,
                    ($size / 3.543307) + 1,
                    $talign
                );
            
             
        }
        
        // now daraw
    
    }
    
    /**
    * get a value from the data
    *
    * eg. $data = array(
    *     'fred' => 'somevalue'
    *
    *  getValue ($data,'fred') returns 'somevalue' 
    * 
    * value can also be a method = eg. getFred()
    *
    * @param   object|array $data 
    * @param   string $v key to get.
    * 
    *
    * @return   string
    * @access   public
    */
  
    function getValue($data,$v) {
        
       // print_R(array("GET VALUE: ", $v));
        // not a method:
        
        if ($v[strlen($v)-1]  != ')') {
            
            $data = (array) $data;
            //echo "<PRE>";print_r(array_keys($data));
            if (false === strpos($v,'.')) {
                if (!isset($data[$v])) {
                  //  echo "missing $v\n";
                    return '';
                }
                if (is_array($data[$v]) || is_object($data[$v])) {
                  //  echo "array/object $v\n";
                    return '';
                }
                //echo "returning $v\n";
                return $data[$v];
            }
            $dd = (array)$data;
            foreach(explode('.', $v) as $vv) {
                if (!is_array($dd)) {
                    return '';   
                }
                if (!isset($dd[$vv])) {
                    return '';
                }
                $dd = is_object($dd[$vv]) ? ((array) $dd[$vv]) : $dd[$vv];
            }
            //echo "ATTEMPT: $v: got $dd\n";
            //exit;
            if (is_array($dd) || is_object($dd)) {
                return '';
            }
            return $dd;
        }
        // method !!!
        if (!is_object($data)) {
            return '';
        }
        $method = substr($v,0,-2);
       
        if (is_object($data) && method_exists($data,$method)) {
           // echo "call $method<BR>";
            $ret = $data->$method();
            // echo "done $method $ret<BR>";
            if (is_array($ret) || is_object($ret)) {
                return '';
            }
            return '' . $ret;
        }
        
        //echo 
        //print_r($data);
        
        //exit;
        return "no method $method in ".get_class($data);
    
    
    }
    
    
    function breakLines($pdf,$str,$x,$y,$h,$align) {
        // do the estimation...
        $len = strlen($str);
 
        $total = $pdf->getStringWidth($str . '      ');
         
        $charsize = $total/$len;
        
        $max_chars = floor(($this->maxWidth / 3.543307) / $charsize);
        //echo "LEN: $len, $total, $charsize, $max_chars";
        $lines = explode("\n",wordwrap($str,$max_chars));
         
        return $this->multiLine($pdf,$lines,$x,$y,$h,$align);
    }
    var $maxWidth = false;
    
    function multiLine($pdf,$lines,$x,$y,$h,$align) {
        // now dealing with mm
        ///XML_SvgToPDF::debug("MULTILINE " .implode("\n",$lines) . " $x, $y, $h");
        $yoffset  = 0;
        $line = -1;
        foreach ($lines as $l=>$v) {
            $line++;
            if ($this->maxWidth !== false && ($pdf->getStringWidth($v) > ($this->maxWidth / 3.543307))) {
                $yoffset += $this->breakLines($pdf,$v,$x,$y + ($l * $h) + $yoffset, $h,$align);
                continue;
            }
            XML_SvgToPDF::debug("TEXT: $x,$y, $l * $h + $yoffset,$v");
            $xoffset = 0;
            if ($align == 'M') { // center
                $xoffset = -1 * ($pdf->getStringWidth($v) / 2);
            }
            if ($align == 'E') { // right/end
                $xoffset = -1 * $pdf->getStringWidth($v);
            }
           
            if ($align == 'J' ) { // justified (eg. started with ==
                $this->justify($pdf, $x , $y + ($l * $h) + $yoffset , $v, $this->justifyLen);
                continue;
            }
            $pdf->text(
                $xoffset + $x ,
                $y + ($l * $h) + $yoffset ,
                $v);
                
        }
        return   ($l * $h) + $yoffset;
        
    }
        
        
    function justify($pdf,$x,$y,$text,$len) {
        if (!strlen(trim($text))) {
            return;
        }
        $bits = explode(' ', $text);
        $textlen = $pdf->getStringWidth(implode('',$bits));
        $spacesize = ($len - $textlen) / (count($bits) -1);
        foreach($bits as $word) {
            $pdf->text($x , $y ,$word );
            $x += $spacesize + $pdf->getStringWidth($word);
        }
    }
        
     

}
