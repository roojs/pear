<?php


class XML_SvgToPDFAlt_Text  extends XML_SvgToPDFAlt_Base { 

    function fromNode($node) {
        parent::fromNode($node);
        if (!isset($this->children[0]->content)) {
            return;
        }
        if (substr($this->children[0]->content,0,2) == '==') {
            $this->style['text-anchor'] = 'justify';
            $this->children[0]->content = substr($this->children[0]->content,2);
        }
    }
      
    function transform() {
        parent::transform();
        if (!@$this->transform) {
            return; 
        }
        if (preg_match('/scale\(([0-9e.-]+),([0-9e.-]+)\)/',$this->transform,$args)) {
		  $xscale = $args[1];
		  $yscale = $args[2];
          $this->style['font-size'] *= $args[1];
        }
    }
    
        
        

    function writePDF($pdf,$data) {
        // set up font.. 
         
        $font = strtolower($this->style['font-family']);
        
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
        if (preg_match('/big5/i',$this->style['font-family'])) {
            $font = 'Big5';
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
        if (!@$this->children) {
            return;
        }
        $lineno = 0;
        foreach($this->children as $i=>$c) {
        
            $xx = isset($c->x) ? $c->x + @$this->xx : $x;
            $yy = isset($c->y) ? $c->y + @$this->yy : $y + ($lineno * $size * 1.3);
            $lineno++;              
            $val = @$c->content;
            
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
                    $args[] = $this->getValue($data,trim($v));
                }
                
                $has_template = preg_match('/%s/', $val);
                     
                $val = vsprintf($val,$args);
                
                if ($has_template && ($font == 'Big5')) {
                    require_once  'Text/ZhDetect.php';
                    $detect = new Text_zhDetect;
                    $type = $detect->guess($val);
                    if ($v == 'S') {
                       
                        $val = @iconv('utf8', 'GB2312//IGNORE', $val);
                        $pdf->setFont('GB' ,
                            $weight,
                            $size);
                    } else {
                        $val = @iconv('utf8', 'BIG5//IGNORE', $val);
                        $pdf->setFont('Big5' ,
                            $weight,
                            $size);
                   }
                }
                
                /*
                
                
                if ($has_template  && ($font == 'Big5')) {
                   
                    
                    $val =    @iconv('utf8', 'utf16be//TRANSLIT', $val);
                    
                    $pdf->setFont('Uni-hw' ,
                        $weight,
                        $size);
                }
                */
            }
            
            $talign = $align;
            if ((!@$this->children[$i+1] ||  !strlen(trim(@$this->children[$i+1]->content))) && ($align == 'J')) {
                $talign = 'L';
            }
            
            
            
            
            
            
            
            
            $yoffset += $this->multiLine($pdf,explode("\n",$val),
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
  
    function iconvert($str) {
        if (is_object($str) || is_array($str)) {
            return $str;
        }
        return  $str ;//. ' - ' . @iconv( "UTF-8" , "Big5//IGNORE", $str  );
    }
  
    function getValue($data,$v) {
        
        // not a method:
        
        if ($v[strlen($v)-1]  != ')') {
            $data = (array) $data;
            if (false === strpos($v,'.')) {
                if (!isset($data[$v])) {
                    return '';
                }
                if (is_array($data[$v]) || is_object($data[$v])) {
                    return '';
                }
                
                return $this->iconvert(@$data[$v]);
            }
            $dd = (array)$data;
            foreach(explode('.', $v) as $vv) {
                if (!is_array($dd)) {
                    return '';   
                }
                if (!isset($dd[$vv])) {
                    return '';
                }
                $dd = is_object($dd[$vv]) ? ((array) $dd[$vv]) : $this->iconvert($dd[$vv]);
            }
            //echo "ATTEMPT: $v: got $dd\n";
            //exit;
            if (is_array($dd) || is_object($dd)) {
                return '';
            }
            return $this->iconvert($dd);
        }
        // method !!!
        if (!is_object($data)) {
            return '';
        }
        $method = substr($v,0,-2);
        if (is_callable(array($data,$method))) {
            $ret = $data->$method();
            if (is_array($ret) || is_object($ret)) {
                return '';
            }
            // not in original!!!
            return $this->iconvert($ret);
        }
        //echo 
        //print_r($data);
        
        //exit;
        return "no method $method in ".get_class($data);
    
    
    }
    
    
    function breakLines(&$pdf,$str,$x,$y,$h,$align) {
        // do the estimation...
        $len = strlen($str);
 
        $total = $pdf->getStringWidth($str . '      ');
         
        $charsize = $total/$len;
        
        $max_chars = floor(($this->maxWidth / 3.543307) / $charsize);
        //echo "LEN: $len, $total, $charsize, $max_chars";
        $lines = explode("\n",wordwrap($str,$max_chars));
         
        return $this->multiLine($pdf,$lines,$x,$y,$h,$align);
    }
    
    function multiLine(&$pdf,$lines,$x,$y,$h,$align) {
        // now dealing with mm
        XML_SvgToPDFAlt::debug("MULTILINE " .implode("\n",$lines) . " $x, $y, $h");
        $yoffset  = 0;
        $line = -1;
        foreach ($lines as $l=>$v) {
            $line++;
            if (@$this->maxWidth && ($pdf->getStringWidth($v) > ($this->maxWidth / 3.543307))) {
                $yoffset += $this->breakLines($pdf,$v,$x,$y + ($l * $h) + $yoffset, $h,$align);
                continue;
            }
            
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
            XML_SvgToPDFAlt::debug("TEXT:   " . ( $xoffset + $x ) . "," .
                ($y + ($l * $h) + $yoffset) . "," . 
                $v);
            
            $pdf->text(
                $xoffset + $x ,
                $y + ($l * $h) + $yoffset ,
                $v);
                
        }
        return   ($l * $h) + $yoffset;
        
    }
        
        
    function justify(&$pdf,$x,$y,$text,$len) {
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
