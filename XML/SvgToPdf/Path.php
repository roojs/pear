<?php

/* output a line

*/

class XML_SvgToPDF_Path  extends XML_SvgToPDF_Base {
    var $d; // stores the details of the path..
    
    
    function fromXmlNode($node) {
        parent::fromXmlNode($node);
        $d = explode(' ',trim($this->d));
        $i=0;
        $data = array();
        
        while ($i < count($d)) {
            $action = $d[$i];
            switch(strtolower($action)) {
                
                
                
                case 'c': // ????
                   
                    $data[] = array('L',$d[$i+3],$d[$i+4]);
                    $i+=7;
                    break;
                case 'm': // move
                case 'l': // line
                    $data[] = array($action,$d[$i+1],$d[$i+2]);
                    $i+=3;
                    break;
                case 'h': // move horizontal
                case 'v': // move horizontal
                    $data[] = array($action,$d[$i+1]);
                    $i+=2;
                    break;
                
                
                case 'z': // close path..
                    $data[] = array($action);
                    $i++;
                    break;
                default:
                    echo "oops found something odd in path? '$action'";
                    echo $this->d;
                    exit;
                    break;
            }
        }
        $this->d = $data;
    }
            
            
        // TODO!! - shift!!!
            
        
        
        
        
     function shift($x,$y) {
        //XML_SvgToPDF::debug('shift');
        //XML_SvgToPDF::debug(array($x,$y));
        //XML_SvgToPDF::debug($this);
        foreach($this->d as $i=>$a) {
            if (count($a) < 2) {
                continue;
            }
            if ($a[0] == 'v') {
                $this->d[$i][1] -= $y;
            } else {
                $this->d[$i][1] -= $x;
                if (isset($this->d[$i][2])) {
                    $this->d[$i][2] -= $y;
                }
            }
        }
        
    }   
        
    
    
    function writePDF($pdf,$data) {
        
        $l = $this->toColor(@$this->style['stroke']);
        if ($l) {
            $pdf->setDrawColor($l[0],$l[1],$l[2]);
        }
        $pdf->setLineWidth($this->style['stroke-width']/ 3.543307);
     
        $c = array();
        /*
         *Not sure why this was added..
        if (count($this->d) > 2) {
            $cc = array();
            foreach($this->d as $a) { 
                 if (count($a) < 2) {
                        continue;       
                 }
                 $x = $a[1] + @$this->xx;
                 $y = $a[2] + @$this->yy;
                 $cc[] = $x/ 3.543307;
                 $cc[] = $y/ 3.543307;
            }
            $pdf->line($cc,0,0,0);
            return;
        }
        */
        

        foreach($this->d as $a) {
            switch($a[0]) {
                case 'M':
                    $x = $a[1] + @$this->xx;
                    $y = $a[2] + @$this->yy;
                    $c = array($x,$y);
                    break;
                
                case 'L':
                    $x = $a[1] + @$this->xx;
                    $y = $a[2] + @$this->yy;
                    $pdf->line($c[0]/ 3.543307,$c[1]/ 3.543307,$x/ 3.543307,$y/ 3.543307);
                    $c = array($x,$y);
                    break;
                default:
                    break;
            }
        }
                
         
    
    
    }
     
        
    


    function toColor($color) {
        if (!$color || ($color == 'none')) {
            return false;
        }
        return array(
            hexdec(substr($color,1,2)),
            hexdec(substr($color,3,2)),
            hexdec(substr($color,5,2)));
        
    }



}
