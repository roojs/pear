<?php

/*
The base node includes:
    fromNode - convert the XML_Tree node into object with vars
        (and parse styles)
    
    // iternate tree with writePDF
    writePDF($pdf,$data) 
    childrenWritePDF($pdf,$data)  

    // shift coordinates of group dynamic elements 
    // so the x is relative to the block corner.
    shiftChildren($x,$y) {
    shift($x,$y) 
    
    // find a dynamic block and calculate how much data it can hold.
    // so you know how many pages are needed.
    calcPerPage()
*/

class XML_SvgToPDF_Base { 

    var $x = 0;
    var $y = 0;
    var $width = 0; // used in svg..
    var $height= 0; 
    
    var $style = array();
    var $children = array();
    
    var $transform = '';
    
    function fromXmlNode($node)
    {
        // extract attributes
        foreach($node->attributes as $k=>$v) {
            if (in_array($k, array('style'))) {
                continue;
            }
            
            $this->$k = $v->value;
            if (preg_match('/[0-9]+mm$/',$v->value)) {
                $vv = str_replace('mm','',$v->value);
                $vv = $vv * 3.543307;
                
                $this->$k = $vv;
                continue;
            }
        }
        // deal with style..
        if ($node->hasAttribute('style') && strlen($node->getAttribute('style'))) {
            
            $s = explode(';',$node->getAttribute('style'));
            foreach($s as $ss) {
                if (!strlen(trim($ss))) {
                    continue;
                }
                if (strpos($ss,':') === false) {
                    $style[$ss] = true;
                    continue;
                }
                $sss = explode(':',$ss);
                if (preg_match('/[0-9]+pt$/',$sss[1])) {
                    $sss[1] =  str_replace('pt','',$sss[1]);
                }
                $style[$sss[0]] = $sss[1];
            }
            $this->style = $style;
        }
      
        $this->transform();
        // if node is a tspan  
       
         
         
    }
    
    
    function fromNode($node) {
        
        if ($node->attributes) {
            foreach($node->attributes as $k=>$v) {
                
                echo $node->name . ":" . $k . "=>". $v. "<BR>";
                
                if (strpos($k,':')) {
                    $kk = explode(':',$k);
                    $k = $kk[1];
                }
                $this->$k = $v;
                if (preg_match('/[0-9]+mm$/',$v)) {
                    $v = str_replace('mm','',$v);
                    $v = $v * 3.543307;
                    
                    $this->$k = $v;
                    continue;
                }
                
            }
        }
        
        if (isset($this->style)) {
            $s = explode(';',$this->style);
            foreach($s as $ss) {
                if (!strlen(trim($ss))) {
                    continue;
                }
                if (strpos($ss,':') === false) {
                    $style[$ss] = true;
                    continue;
                }
                $sss = explode(':',$ss);
                if (preg_match('/[0-9]+pt$/',$sss[1])) {
                    $sss[1] =  str_replace('pt','',$sss[1]);
                }
                $style[$sss[0]] = $sss[1];
            }
            $this->style = $style;
        }
                
        
        if ($node->content) {
            $this->content = trim($node->content);
               echo $node->name . ":CONTENT=>". $node->content. "<BR>";
        }
        if ($node->children) {
            $this->children = $node->children;
        }
        echo "<PRE>BEFORE:";print_r($this->toArray());
        $this->transform();
        echo "<PRE>AFTER:";print_r($this->toArray());
    }


    function transform() {
        if (empty($this->transform)) {
            return;
        }
        // do not transform tspans -- done by overwriting this...
        //if ($this->x === false) {
        //    return;
       // }
        
        // deal with transformation..
        $tr = $this->transform;
        if (preg_match('/scale\(([0-9e.-]+),([0-9e.-]+)\)/',$tr,$args)) {
            $xscale = $args[1];
            $yscale = $args[2];
            $method = 'scale';
            } else if (preg_match('/matrix\(([0-9e.-]+),([0-9e.-]+),([0-9e.-]+),([0-9e.-]+),([0-9e.-]+),([0-9e.-]+)\)/',$tr,$args)) {
            array_shift($args);
            require_once 'Math/Matrix.php';
            $matrix =  new Math_Matrix( array(
                    array($args[0],$args[2],$args[4]),
                    array($args[1],$args[3],$args[5]),
                    array(       0,       0, 	1))
            );
            $method = 'matrix';
        } else if (preg_match('/translate\(([0-9e.-]+),([0-9e.-]+)\)/',$tr,$args)) {
            $x = $args[1];
            $y = $args[2];
            $method = 'translate';
        } else {
            echo "<PRE>no match?";print_r($this); exit;
            return;
        }
        //
        switch ($method) {
            case 'scale':
                $this->x *=  $xscale;
                $this->y *=  $yscale;
                if (empty($this->children)) {
                    return;
                }
                foreach(array_keys($this->children) as $i) {
                    if ($this->children[$i]->x === false) {
                        continue;
                        // echo "<PRE>";print_r($this);exit;
                    }
                    $this->children[$i]->x *=  $xscale;
                    $this->children[$i]->y *=  $yscale;
                }
                break;
            case 'matrix':
                $v = new Math_Vector(array($this->x,$this->y,0));
                
                $r = $matrix->vectorMultiply($v);
                $r = $r->getData();
                $this->x = $r[0];
                $this->y = $r[1];
                //echo "<PRE>";var_dump(	$r);exit;
                if (empty($this->children)) {
                    return;
                }
                foreach(array_keys($this->children) as $i) {
                    if ($this->children[$i]->x === false) {
                        continue;
                        // echo "<PRE>";print_r($this);exit;
                    }
                    $v = new Math_Vector(array($this->children[$i]->x,$this->children[$i]->y,0));
                    $r =  $matrix->vectorMultiply($v);
                    $r = $r->getData();
                    $this->children[$i]->x = $r[0];
                    $this->children[$i]->y = $r[1];
     
                }
                break; 
            case 'translate':
                if ($this->x !== false &&  $this->y !== false) {
                         
                   $this->x +=  $x;
                   $this->y +=  $y;
                }
                if (empty($this->children)) {
                    return;
                }
                foreach(array_keys($this->children) as $i) {
                    if ($this->children[$i]->x === false || $this->children[$i]->y === false) {
                        continue;
                        // echo "<PRE>";print_r($this);exit;
                    }
                    $this->children[$i]->x +=  $x;
                    $this->children[$i]->y +=  $y;
                }
                break; 
                 
          }
     }




    
    function writePDF($pdf,&$data) {
        $this->childrenWritePDF($pdf,$data);
    }
    
    function childrenWritePDF(&$pdf,&$data) {
        if (!@$this->children) {
            return;
        }
        foreach(array_keys($this->children) as $k) {
            if (empty($this->children[$k])) {
                continue;
            }
            if (!method_exists($this->children[$k],'writePDF')) {
                echo "OOPS unknown object? <PRE>" ; print_r($this->children[$k]); exit;
            }
            $this->children[$k]->writePDF($pdf,$data);
        }
    }
    
    
    // add the values to the children..
    function shiftChildren($x,$y) {
        if (empty($this->children)) {
            return;
        }
        foreach(array_keys($this->children) as $k) {
            if (!$this->children[$k]) {
                continue;
            }
            $this->children[$k]->shift($x,$y);
        }
    }
    
    function shift($x,$y) {
        //XML_SvgToPDF::debug('shift');
        //XML_SvgToPDF::debug(array($x,$y));
        //XML_SvgToPDF::debug($this);
        
        if ($x === false) {
            return;
        }
        
        //var_dump(array("SHIFT", $x, $y, "TO: ", $this->x , $this->y));
        if ($this->x !== false) {
            $this->x += $x;
        }
        if ($this->y !== false) {
            $this->y += $y;
        }
        //XML_SvgToPDF::debug($this);
        $this->shiftChildren($x,$y);
    }
    function calcPerPage() {
        $ret = array();
        foreach($this->children as $n) {
            if (!$n) {
                continue;
            }
            if (!is_a($n, 'XML_SvgToPDF_G')) {
                continue;
            }
            if (!isset($n->settings) || !isset($n->settings['dynamic']))  {
                continue;
            }
            
            
            $rows  = isset($n->settings['rows']) ? $n->settings['rows'] : 1;
            $cols  = isset($n->settings['cols']) ? $n->settings['cols'] : 1;
           // return array($n->settings['dynamic'], $rows * $cols);

             
            $ret[$n->settings['dynamic']] =  $rows * $cols;
            
            
            
            
        }
        //return array('',0);

        return $ret;
         
    
    
    
    
    }
    
    function toColor($color) {
        if (!$color || ($color == 'none')) {
            return false;
        }
        
        if ($color == 'black') {
            $color = '#000000';
        }
        
        return array(
            hexdec(substr($color,1,2)),
            hexdec(substr($color,3,2)),
            hexdec(substr($color,5,2)));
        
    }
    function toArray()
    {
        $ret = array();
        $ar = (array) $this;
        $ret['__CLASS__'] = get_class($this);
        foreach($ar as $k=>$v) {
            if (is_array($v) || is_object($v)) {
                $ret[$k] = "**ARRAY|OBJECT**";
                continue;
            }
            $ret[$k] = $v;
        }
        return $ret;    
    }
    
    
}
