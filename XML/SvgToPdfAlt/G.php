<?php

/* code that deals with svg groups
it does alot of smart stuff to handle 'dynamic' blocks

*/


class XML_SvgToPDFAlt_G     extends XML_SvgToPDFAlt_Base { 

    function fromNode($node) {
        parent::fromNode($node);
          // look for 
        if (!@$this->children) {
            return;
        }
        $settings = array(
            'rows' => 1,
            'cols' => 1
        );
        
        $isDynamic = false;
        foreach(array_keys($this->children) as $k) {
            if (!is_a($this->children[$k], 'XML_SvgToPDF_Text')) {
                continue;
            }
            
            if (!isset($this->children[$k]->children[0]->content) || 
                    (strpos($this->children[$k]->children[0]->content,'=') === false)) {
                
                continue;
            }
              
            foreach($this->children[$k]->children as $o) {
                list($l,$r) = explode('=',$o->content);
                $settings[$l] = $r;
            }
             
            
            unset($this->children[$k]);
            $isDynamic = true;
            break;
        }
      
        if (!$isDynamic) {
            return;
        }
        
        
        
         //look for the bounding box..
        $boundingbox = false;
        foreach(array_keys($this->children) as $k) {
            if (!is_a($this->children[$k], 'XML_SvgToPDF_Rect')) {
               continue;
            }
            if (@$this->children[$k]->nonprintable == 'true') {
                $boundingbox = clone($this->children[$k]);
                $this->children[$k]->style['fill'] = 'none';
               // unset($this->children[$k]);
            }
        }
        if (!$boundingbox) {
            return;
        }
        
        $this->boundingbox = $boundingbox;
        $this->settings = $settings;
        $this->shiftChildren($this->boundingbox->x,$this->boundingbox->y);
    }
    
    function shift($x,$y) {
        
        if ($this->boundingbox) {
            return;
        }
        
         $this->shiftChildren($x,$y);
    
    }



    function writePDF($pdf,$data) {
        // g group = does it have a 
        // look for 
        if (!@$this->children) {
            return;
        }
         
        
        if (empty($this->settings)) {
            return $this->childrenWritePDF($pdf,$data);
        }
        $use = false;
        if (substr($this->settings['dynamic'],-2,2) == '()') {
        
            $use = $data->{substr($this->settings['dynamic'],0,-2)}();
            
        } else {
            $use = @$data[$this->settings['dynamic']];
        }
        
            
        if ($use === false) {
            return;
        }
        
        if (!is_array($use) || !$use) {
            return $this->childrenWritePDF($pdf,$data);
        }
        
        
        
        
        $this->x = $x = $this->boundingbox->x;
        $this->y =$y = $this->boundingbox->y;
        $w = $this->boundingbox->width;
        $h = $this->boundingbox->height; 
        
        
        //echo '<PRE>';print_r($this);exit;
        // shift... ** this does not handle groups!!!
      
        //print_R($use);
        $keys = array_keys($use);
        $kpos = 0;
        $kmax = count($keys);
        //XML_SvgToPDF::debug(array($x,$y,$w,$h));
        //XML_SvgToPDF::debug($keys);
        XML_SvgToPDF::debug($this->settings);
        for($r=0;$r<$this->settings['rows'];$r++) {
            $yy = $y + ($r*$h);
            for($c=0;$c<$this->settings['cols'];$c++) {
                $xx = $x + ($c*$w);
                XML_SvgToPDF::debug(array($xx,$yy));
                foreach(array_keys($this->children) as $k) {
                    if (!$this->children[$k]) {
                        continue;
                    }
                    $this->children[$k]->xx = $xx;
                    $this->children[$k]->yy = $yy;
                    $this->children[$k]->maxWidth = $w - 20; 
                    $this->children[$k]->writePDF($pdf,$use[$keys[$kpos]]);
                }
                $kpos++;
                if ($kpos >= $kmax) {
                    break 2;
                }
            }
        }
        
        
        
    }




}
