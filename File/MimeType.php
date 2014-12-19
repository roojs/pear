<?php
/*
$y = new File_MimeType();
//var_dump($y->fromExt('doc'));

$y = new File_MimeType();
var_dump($y->fromFilename('fred.doc'));


$y = new File_MimeType();
//var_dump($y->toExt('application/x-pdf'));

require_once '../Pman/DataObjects/Documents.php';

$x = new Pman_DataObjects_Documents();
$ar = $x->mimeTypeMap();

foreach($ar as $ext=>$mt) {
    $calc = $y->fromExt($ext);
    if ($calc != $mt) {
        echo "array( '$mt', array('$ext')),<BR>";
    }
}
*/

$GLOBALS['File_MimeType'] = array();

class File_MimeType
{
    function File_MimeType()
    {
        
        $this->load();
        
    }
    function load()
    {
        if (!empty($GLOBALS['File_MimeType'] )) {
            return;
        }
        $mtl = array();//&$GLOBALS['File_MimeType'];
        $f = '/etc/mime.types';
        if (!@file_exists($f)) { // open_basedir..
            $f= dirname(__FILE__).'/mime.types';
        }
        $ar = file($f);
        foreach($ar as $l) {
            $l = strtolower( trim($l) );
            if (!strlen($l) || $l[0] == '#') {
                continue;
            }
            $ex = preg_split('/\s+/', $l);
            if ((count($ex) == 1)) {
                continue;
            }
            $mt = array_shift($ex);
            
            $mtl[] = array($mt, $ex);
            
        }
        
        $GLOBALS['File_MimeType'] = array_merge(
            array(
                // override!!!!
                array( 'application/octet-stream', array('dat')),
                array( 'application/vnd.dwg', array('dwg')),
                array( 'application/mswordapplication', array('doc')),
                array( 'application/vnd.openxmlformats-officedocument.presentationml.template', array('potx')),
                array( 'application/vnd.openxmlformats-officedocument.presentationml.slideshow', array('ppsx')),
                array( 'application/vnd.openxmlformats-officedocument.presentationml.presentation', array('pptx')),
                array( 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', array('xlsx')),
                array( 'application/vnd.openxmlformats-officedocument.spreadsheetml.template', array('xltx')),
                array( 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', array('docx')),
                array( 'application/vnd.openxmlformats-officedocument.wordprocessingml.template', array('dotx')),
                array( 'application/voicexml+xml', array('vxml')),
                array( 'application/x-futuresplash', array('spl')),
                array( 'application/x-netcdf', array('cdf')),
                array( 'application/x-tex', array('tex')),
                array( 'application/xslt+xml', array('xslt')),
                array( 'application/xml-dtd', array('dtd')),
                array( 'audio/x-mpegurl', array('m3u')),
                array( 'audio/mpeg', array('mp3')),
                array( 'application/vnd.rn-realmedia', array('rm')),
                array( 'image/bmp', array('bmp')),
                array( 'image/cgm', array('cgm')),
                
                array( 'image/x-icon', array('ico')),
                array( 'image/vnd.microsoft.icon', array('ico')),
                
                array( 'image/svg', array('svg')),
                
                array( 'text/calendar', array('ifb')),
                array( 'text/csv', array('csv')),
                array( 'text/xml', array('xml')),
                array( 'text/sgml', array('sgml')),
                array( 'text/sgml', array('sgm')),
                // bjs is a Pman json file..
                array( 'text/plain', array('txt','asc', 'text','pot','brf', 'bjs')),
                
                array( 'video/vnd.mpegurl', array('m4u')),
                array( 'video/x-flv', array('flv')),
                array( 'video/ogg', array('ogv')),
            
            
            ), 
            
            $mtl);
        
        // fixes...
        
        
        ///echo '<PRE>'; var_dump($mtl);
    }
    
    function fromExt($ext) 
    {
        //echo "LOOKUP" ; var_dump($ext);
        $mtl = $GLOBALS['File_MimeType'];
        $ext = strtolower($ext);
        foreach($mtl as $mtd) {
            if (in_array($ext, $mtd[1])) {
                return $mtd[0];
            }
        }
        return 'application/octet-stream';
    }
    
    function fromFilename($fn)
    {
        $ar = explode('.', $fn);
        return $this->fromExt(array_pop($ar));
    }
    
    function toExt($mtype) 
    {
        $mtype = strtolower($mtype);
        $mtl = $GLOBALS['File_MimeType'];
        foreach($mtl as $mtd) {
            if ($mtd[0] == $mtype) {
                return $mtd[1][0];
            }
        }
        
        return '';
    }
    
    
}


