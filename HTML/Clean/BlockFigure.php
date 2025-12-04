<?php 

/**
 * This handles 'figure' tags (which is what img's are auto wrapped with.)
 */

 
require_once 'Block.php';
class  HTML_Clean_BlockFigure extends HTML_Clean_Block
{
    
    function __construct($cfg) {
        if (!empty($cfg['node'])) {
            $this->readElement($cfg['node']);
            $this->updateElement($cfg['node']);
        }
        parent::__construct($cfg);
    }
     
  
    
    // setable values.
    var $image_src= '';
    var $align= 'center';
    var $caption = '';
    var $caption_display = 'block';
    var $width = '100%';
    var $cls = '';
    var $href = '';
    var $video_url = '';
    var $image_width = 0;
    var $image_height = 0;
    // margin: '2%', not used
    
    var $text_align = 'left'; //   (left|right) alignment for the text caption default left. - not used at present

    
    // used by context menu
    
    /**
     * create a DomHelper friendly object - for use with
     * Roo.DomHelper.markup / overwrite / etc..
     */
    function toObject ()
    {
        $doc = new DOMDocument('1.0', 'utf8');
        
        // alt text for the image
        $this->caption = '<b>test caption</b>';
        $alt = '';
        if(!empty($this->caption)) {
            $d = $doc->createElement('div');
            $f = $doc->createDocumentFragment();
            $f->appendXML($this->caption); // caption could include html
            $d->appendChild($f);
            $alt = trim(
                str_replace('"', '&quot;',
                    preg_replace('/\s+/', ' ',
                        str_replace("\n", " ", $d->textContent)
                    )
                )
            );
        }
        
        // margin
        $m = $this->width != '100%' && $this->align == 'center' ? '0 auto' : 0; 
        
        // image width
        $iw = $this->align == 'center' ? $this->width : '100%';

        // image element array
        $img =   array(
            'tag' => 'img',
            'contenteditable' => 'false',
            'src' => $this->image_src,
            'alt' => $alt,
            'style'=> array(
                'width' => $iw,
                'max-width' => $iw . ' !important', 
                'margin' => $m  
            ),
            'width' => $iw
        );
        
        // if href is set, wrap the image in a link
        if (!empty($this->href)) {
            $img = array(
                'tag ' => 'a',
                'contenteditable' => 'false',
                'href' => $this->href,
                'cn' => array(
                    $img
                )
            );
        }

        $image_width = $this->image_width * 1;
        $image_height = $this->image_height * 1;

        
        // if video url is set, wrap the image in a video div
        if (!empty(strlen($this->video_url))) {
            $img = array(
                'tag' => 'div',
                'cls' => $this->cls,
                'frameborder' => 0,
                'allowfullscreen' => true,
                'width' => 768,  // these are for video tricks - that we replace the outer
                'height' => (!empty($image_width) && !empty($image_height)) ? (round(768 / $image_width * $image_height)) : 576,
                'src' => $this->video_url,
                'cn' => array(
                    $img
                )
            );
        }

        $ret = array(
            'tag' => 'figure',
            'data-block' => 'Figure',
            'data-width' => $this->width,
            'data-caption' => $this->caption, 
            'data-caption-display' => $this->caption_display,
            'data-image-width' => $this->image_width,
            'data-image-height' => $this->image_height,
            'contenteditable' => 'false',
            'style' => array(
                'display' => 'block',
                'float' =>  $this->align,
                'maxWidth' =>  $this->align == 'center' ? '100% !important' : ($this->width . ' !important'),
                'width' => $this->align == 'center' ? '100%' : $this->width,
                'margin' =>  '0px',
                'padding' => $this->align == 'center' ? '0' : '0 10px' ,
                'textAlign' => $this->align
                
            ),
            'align' => $this->align,
            'cn' => array(
                $img
            )
        );

        // show figcaption only if caption_display is 'block'
        if($this->caption_display == 'block') {
            $ret['cn'][] = array(
                'tag' => 'figcaption',
                'style' => array(
                    'textAlign' => 'left',
                    'fontSize' => '16px',
                    'lineHeight' => '24px',
                    'display' => $this->caption_display,
                    'maxWidth' => ($this->align == 'center' ?  $this->width : '100%' ) . ' !important',
                    'margin' => $m,
                    'width' => $this->align == 'center' ?  $this->width : '100%' 
                ),
                'cls' => strlen($this->cls) > 0 ? ($this->cls  . '-thumbnail' ) : '',
                'cn' => array(
                    'tag' => 'div',
                    'style' => array(
                        'marginTop' => '16px',
                        'textAlign' => 'start'
                    ),
                    'align' => 'left',
                    'cn' => array(
                        'tag' => 'i',
                        'contenteditable' => 'true',
                        'html' => strlen($this->caption) ? $this->caption : "Caption" // fake caption
                    )
                )
            );
        }

        return $ret;
    }
    
    function readElement ($node)
    {
        // this should not really come from the link...
        $this->video_url = $this->getVal($node, 'div', 'src');
        $this->cls = $this->getVal($node, 'div', 'class');
        $this->href = $this->getVal($node, 'a', 'href');
        
        
        $this->image_src = $this->getVal($node, 'img', 'src');
         
        $this->align = $this->getVal($node, 'figure', 'align');
        
        $figcaption = $this->getVal($node, 'figcaption', false);
        if ($figcaption !== '') {
            $this->caption = $this->getVal($figcaption, 'i', 'html');
        }
        

        $this->caption_display = $this->getVal($node, 'figcaption', 'data-display');
        //$this->text_align = $this->getVal(node, 'figcaption', 'style','text-align');
        $this->width = $this->getVal($node, true, 'data-width');
        //$this->margin = $this->getVal(node, 'figure', 'style', 'margin');
        
    }
    
    
    
    
}