<?php

/* code that deals with svg groups
it does alot of smart stuff to handle 'dynamic' blocks

*/


class XML_SvgToPDF_G     extends XML_SvgToPDF_Base
{ 
    var $boundingbox = false; // for repeats...
    var $settings = array();  // cols/rows..

    function fromXmlNode($node)
    {
       // print_r("G:fromXmlNode");
        parent::fromXmlNode($node);
 
        
        if (empty($this->dynamic)) {
            return;
        }
        $settings = array(
            'rows' => $this->rows,
            'cols' => $this->cols,
            'dynamic' => $this->dynamic
        );
        
        
         //look for the bounding box..
        $boundingbox = false;
//echo "<PRE>";print_r($this->children);exit;
        foreach(array_keys($this->children) as $k) {
            if (!is_a($this->children[$k], 'XML_SvgToPDF_Rect')) {
               continue;
            }
            if (empty($this->children[$k]->nonprintable) || ($this->children[$k]->nonprintable != 'true')) {
                continue;
            }
 //           echo "SETTING BOUNDING BOX"; exit; 
            $boundingbox = clone($this->children[$k]);
            // box will be rendered..
            $this->children[$k]->style['fill'] = 'none';
               // unset($this->children[$k]);
          
        }
        if (!$boundingbox) {
            return;
        }
        //echo "<PRE>";print_r($boundingbox ); exit;
        
        $this->boundingbox =  $boundingbox ;
        $this->settings = $settings;
        
        // change the X/Y values of all the child elements..
         
        
        $this->shiftChildren(-1* $this->boundingbox->x,-1 * $this->boundingbox->y);
        //$this->shiftChildren($this->boundingbox->x,$this->boundingbox->y);
      
    }

    // not sure why this is done twice?

    function fromNode($node) {
        parent::fromNode($node);
        
    
//----------- applyDynamic...        
        
          // look for 
        if (empty($this->children)) {
            return;
        }
        
        if (empty($this->dynamic)) {
            return;
        }
        $settings = array(
            'rows' => $this->rows,
            'cols' => $this->cols,
            'dynamic' => $this->dynamic
        );
         
        
        
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
        //echo "<PRE>";print_r($boundingbox );
        
        $this->boundingbox =  $boundingbox ;
        $this->settings = $settings;
        $this->shiftChildren($this->boundingbox->x,$this->boundingbox->y);
    }
    
    function shift($x,$y) {
        
        if ($this->boundingbox) {
            return;
        }
        
        $this->shiftChildren($x,$y);
    
    }



    function writePDF($pdf,&$data) {
        // g group = does it have a 
        // look for 
        if (empty($this->children)) {
            return;
        }
         
        // not dynamic.. -> just dump..
        if (empty($this->settings)) {
            return $this->childrenWritePDF($pdf,$data);
        }
        
        $use = false;
        if (substr($this->settings['dynamic'],-2,2) == '()') {
        
            $use = $data->{substr($this->settings['dynamic'],0,-2)}();
            
        } else {
            $use = empty($data[$this->settings['dynamic']]) ? ''  : $data[$this->settings['dynamic']];
        }
        
            
        if (empty($use)) {
            return;
        }
        // if use is a value  - make it an array with a single element, so that the bounding box
        // additions apply..
        if (!is_array($use)) {
            $use = array($use);
        }
        
        // echo "<PRE>";print_r($boundingbox );
        
        
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
        
        $yy = $y;
        
        for($r=0;$r<$this->settings['rows'];$r++) {
            $record = $use[$keys[$kpos]];
            
            
            for($c=0;$c<$this->settings['cols'];$c++) {
                
                $record = $use[$keys[$kpos]];
                
                $xx = $x + ($c*$w);
                XML_SvgToPDF::debug(array($xx,$yy));
                foreach(array_keys($this->children) as $k) {
                    if (!$this->children[$k]) {
                        continue;
                    }
                    
                   //  if (is_object($use[$keys[$kpos]]) && method_exists($use[$keys[$kpos]], 'loadSvg')) {
                   //     // set the defaults, as we cant do it in the thing now..
                   //     $use[$keys[$kpos]]->loadSvg();
                   // }
                    
                    
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
            $yy += !empty($record->userows) ? ($record->userows) * $h : $h;

            
        }
        
        
        
    }




}
