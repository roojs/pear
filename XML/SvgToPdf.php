<?php

/* usage:
     
       *install Fpdf as a PEAR package by:
        pear install http://www.akbkhome.com:81/svn/akpear/Fpdf/Fpdf-1.51.tgz
     
       
        $data=array(
            
            'address' => array(
                array(
                    'name' => = "Xxxxxxxx xxxxxxxxxxxx xxxxxxxxxx xxxxxxx Xxxxxxxx xxxxxxxxxxxx xxxxxxxxxx xxxxxxx";
                ),
                array(
                    'name' => = "Xxxxxxxx xxxxxxxxxxxx xxxxxxxxxx xxxxxxx Xxxxxxxx xxxxxxxxxxxx xxxxxxxxxx xxxxxxx";
                ),
                array(
                    'name' => = "Xxxxxxxx xxxxxxxxxxxx xxxxxxxxxx xxxxxxx Xxxxxxxx xxxxxxxxxxxx xxxxxxxxxx xxxxxxx";
                ),
        );
        $pdf = XML_SvgToPDF::construct(dirname(__FILE__).'/test.svg',$data);
        
        $pdf->output();
        
        
        
        expects an svg file (probably made by sodipodi)
        a block is the group, 
        a) it has a text component with
            dynamic=address
            rows=7
            cols=3
        b) a non-printable rectangle (which is the bounding box)
        c) some text {xxxx}{yyyy} which is replaced with 
            address[0....][xxxx] = 'somevalue;
            address[0....][yyyy] = 'somevalue;
        
        
        
        
*/


// current options for generated file..

$GLOBALS['_XML_SVGTOPDF']['options'] = array(
    'debug' => 0,
    );
 
        
class XML_SvgToPDF {

    var $language;
    
    static function debug($s,$e=0) {
        if (!$GLOBALS['_XML_SVGTOPDF']['options']['debug']) {
            return;
        }
        echo "<PRE>".print_R($s,true). "</PRE>";
        if ($e) { 
            exit; 
        }
    }
    
    
    /**
     * 
     * Static ! not sure why!?
     * 
     */
    static function construct($svg, $data=array()) 
    {  
        if (is_object($data)) {
            $data = (array) $data;
        }
      
        $t = new XML_SvgToPDF;
        
        $t->language =  $data['language'];
        require_once 'XML/Tree/Morph.php';

        $x = new XML_Tree_Morph( 
                    $svg,
                    array(
                       'debug' => 0,
                       'filter' => array(
                           'svg'    => array($t, 'buildObject'),
                           'image'    => array($t, 'buildObject'),
                           'text'    => array($t, 'buildObject'),
                           'tspan'   => array($t, 'buildObject'),
                           'rect'   => array($t, 'buildObject'),
                           'g'   =>  array($t, 'buildObject'),
                           'path'   =>  array($t, 'buildObject'),
                           'sodipodi:namedview' =>  array($t, 'buildNull'),
                           'defs' =>  array($t, 'buildNull'),
                        )
                    )
                 );
        
        $tree = $x->getTreeFromFile();
       //echo '<PRE>'; print_R($tree);      exit;
        $tree = $t->buildobject($tree);
       //   echo '<PRE>'; print_R($tree);      exit;
         //echo "<PRE>";
       // $tree = $t->parseSvg($svg);
        // echo "<PRE>";print_r($tree);exit;
        
        
        
        
        
        //echo "<PRE>";print_r($tree);exit;
        $w = empty($tree->width) ? 0 : $tree->width;
        $h = empty($tree->height) ? 0 : $tree->height;
        $orientation =  (preg_replace('/[^0-9.]+/','', $w)*1) > (preg_replace('/[^0-9.]+/','', $h)*1) ? 'L' : 'P';
        //var_dump($orientation);exit;
        $GLOBALS['_XML_SVGTOPDF']['options']['file'] = $svg;

        if ($data['language'] == 'big5') {
          //die("trying chinese");
            
               
            require_once 'Fpdf/Unicode.php';

            $pdf=new FPDF_Unicode($orientation ,'mm','A4');
            $pdf->AddGBFont();
            $pdf->AddBig5Font();
            $pdf->AddUniCNShwFont(); 
            $pdf->open();            

         
        } else {
            require_once  'Fpdf/tFPDF.php' ;

            $pdf=new tFPDF($orientation ,'mm','A4');
            $pdf->open();
        }

        $pdf->setAutoPageBreak(false);
        $pdf->AliasNbPages();
        // convert data to array.
        if (is_object($data)) {
            $data = (array) $data;
        }
        // assoc. array of key => no of fields per page.
         $perPage = $tree->calcPerPage();
        //list($var,$perpage) = $tree->calcPerPage();
        //if (empty($data) || !@$var || !@count($data[$var])) {
//         print_r("<PRE>");
//          print_r($data['transactions'][0]);
//          $data['transactions'][0]->desc = 'abcdefghijklmnopqrstuvwxyz Z';
//           print_r($data['transactions'][0]->desc);
        // no dynamic blocks:
         if (!$perPage || empty($data)) {
            $pdf->addPage();
            $tree->writePDF($pdf,$data);
            $t->debug($tree);
            return $pdf;
        }
        
        // build blocks of data for each page.
        $haveData = true;
        $page = 0;
        
       //    $originalData = $data;
        //$alldata = $data[$var];

       //  while (count($alldata))  {
        //print_r($perPage);exit;
        
        
        
         
        while (true == $haveData ) {
            $page_data = $data;
            $haveData = false;
            //print_r($perPage);
            
            // replaces the properties that have 'page data'
            
            
            foreach($perPage as $k=>$v) {
                if (empty($data[$k])) {
                    $page_data[$k] = array();
                    continue;
                }
                $haveData = true;
                $page_data[$k] = self::fetchRows($data,$k,$v);
                
                
                //$page_data[$k] = array_splice ( $data[$k], 0,$v);
            }
            
           
             
            if ($page && !$haveData) {
                break;
            }
            $page++;
                
            $t->debug("<B>PAGE $page<B>");
            
            $pdf->addPage();

            $tree->writePDF($pdf,$page_data);
                
      
            //$tree->writePDF($pdf,$data);
        }
       
        $t->debug($tree);
        return $pdf;
    }
    
    static function fetchRows(&$original_data, $key, $rows) {
        $ret = array();
        while ($rows > -1 && !empty($original_data[$key])) {
            $addrow = array_shift($original_data[$key]);
            $rows -= !empty($addrow->userows) ? $addrow->userows : 1;
            if ($rows < 0) {
                array_unshift($original_data[$key],$addrow);
                break;
            }
            $ret[] = $addrow;
            
        }
        return $ret;
        
        
    }
    
    
    
    function parseSvg($svgFileName)
    {
        libxml_use_internal_errors(true);
        $d = new DOMDocument();
        $d->load($svgFileName);
       // print_r($d);
        return $this->parseNode($d->documentElement);
    }
    
    function parseXMLNode($n)
    {
        // do children first..
        //print_r(array("PARSENODE:",$n));
        if (empty($n)) {
            return array();
        }
        $children = array();
        if ($n->childNodes->length) {
            foreach($n->childNodes as $cn) {
                if ($cn->nodeType != XML_ELEMENT_NODE) {
                    continue;
                }
                $child = $this->parseNode($cn);
                if (is_array($child) && count($child)) {
                    $children = array_merge($children, $child);
                    continue;
                } 
                if (is_object($child)) {
                    $children[] = $child;
                }
                continue;
                
            }
        }
        if (!in_array($n->tagName, array('svg','image','text', 'tspan', 'rect', 'g', 'path'))) {
            return $children;
            
        }
        // fixme.. this is the Dom Version..
        $ret = $this->buildObject($n,$children);
        
        return $ret;
    }
      
    
    
    
    
    
    function buildNull($node) {
        return;
    }
    function buildObject($node )
    {
        require_once 'XML/SvgToPdf/Base.php';
        
        return XML_SvgToPDF_Base::factory($node);
         
    }
    
    


}
