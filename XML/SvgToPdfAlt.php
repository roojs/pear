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

require_once 'XML/Tree/Morph.php';
require_once 'XML/SvgToPdfAlt/Base.php';

// current options for generated file..

$GLOBALS['_XML_SVGTOPDF']['options'] = array(
    'debug' => 0,
    );
 
        
class XML_SvgToPDFAlt {

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
    static function construct($svg, $data=array()) {

        $t = new XML_SvgToPDFAlt;
        $t->language = @$data['language'];
        if (empty($svg)) {
            trigger_error(__CLASS__.':construct called without svg', E_USER_ERROR);
        }
       
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
                           'metadata' =>  array($t, 'buildNull'),
                           
                        )
                    )
                 );

        $tree = $x->getTreeFromFile();
        
        
        ///echo '<PRE>'.htmlspecialchars(print_r($tree,true));exit;
        
        $tree = $t->buildobject($tree);
 //echo '<PRE>'.htmlspecialchars(print_r($tree,true));
        //echo "<PRE>";print_r($tree);exit;
        $orientation =  ($tree->width > $tree->height) ? 'L' : 'P';

        $GLOBALS['_XML_SVGTOPDF']['options']['file'] = $svg;

        if ($data['language'] == 'big5') {
           
            require_once 'FpdfAlt/Chinese.php';

            $pdf=new FpdfAlt_Chinese($orientation ,'mm','A4');
            $pdf->AddGBFont();
            $pdf->AddBig5Font();
            $pdf->AddUniCNShwFont(); 
            $pdf->open();            
        } else {
            require_once 'Fpdf.php'; 

            $pdf=new FPDF($orientation ,'mm','A4');
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
        while (true == $haveData ) {
            $page_data = $data;
            $haveData = false;
            foreach($perPage as $k=>$v) {
                if (!$data[$k]) {
                    $page_data[$k] = array();
                    continue;
                }
                $haveData = true;
                $page_data[$k] = array_splice ( $data[$k], 0,$v);
            }
            $page++;
            if (!$haveData) {
                break;
            }
                
            $t->debug("<B>PAGE $page<B>");
            $pdf->addPage();
           // echo '<PRE>'.htmlspecialchars(print_r($page_data,true));
            $tree->writePDF($pdf,$page_data);
        }
        
        $t->debug($tree, true);
        return $pdf;
    }
    
    function buildNull($node) {
        return;
    }
    function buildObject($node) {
        $class = 'XML_SvgToPDFAlt_'.$node->name;
        
        if (!empty($node->content) && strlen(trim($node->content)) && (@$this->language)) {
            $node->language = $this->language;
 
        }
 

        //echo "look for $class?";
        if (!class_exists($class) && !empty($node->name)) {
            // try loading it..
            $file = dirname(__FILE__) . '/SvgToPdfAlt/'.ucfirst(strtolower($node->name)). '.php';
            $this->debug("loading  $file");
            if (file_exists($file)) {
                require_once 'XML/SvgToPdfAlt/'.ucfirst(strtolower($node->name)) . '.php';
            }
        }
        // now if it doesnt exist..
        if (!class_exists($class)) {
            $this->debug("can not find $class");
           $class = 'XML_SvgToPDFAlt_Base';
        }
        //echo '<PRE>';print_r($node);
        
        $r = new $class;
        $r->fromNode($node);
        return $r;
    }
    
    


}
