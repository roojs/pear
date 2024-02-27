<?php

/* output a rectangle

*/

class XML_SvgToPDFAlt_Image  extends XML_SvgToPDFAlt_Base {
    function writePDF($pdf,$data) {
    
    
        $dir = dirname($GLOBALS['_XML_SVGTOPDF']['options']['file']);
        $pdf->Image($dir .'/'.basename($this->href), $this->x/ 3.543307, $this->y/ 3.543307, $this->width/ 3.543307,$this->height/ 3.543307);
     
    
    }

 


}
