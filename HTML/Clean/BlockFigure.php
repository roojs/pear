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
        
        // plain text caption
        // $this->caption = '<b>test caption</b>';
        $caption_plain = '';
        if(!empty($this->caption)) {
            $d = $doc->createElement('div');
            $f = $doc->createDocumentFragment();
            $f->appendXML($this->caption); // caption could include html
            $d->appendChild($f);
            var_dump($d->textContent);
            $caption_plain = $this->caption_display == "block" ? trim(preg_replace('/\s+/', ' ', str_replace("\n", " ", $d->textContent))) : '';
        }
        
        // margin
        $m = $this->width != '100%' && $this->align == 'center' ? '0 auto' : 0; 
        
        // image width
        $iw = $this->align == 'center' ? $this->width : '100%';

        $img =   array(
            'tag' => 'img',
            'src' => $this->image_src,
            'alt' => $caption_plain,
            'style'=> array(
                'width' => $iw,
                'max-width' => $iw . ' !important', 
                'margin' => $m  
                
            )
        );

        var_dump($img);
        die('test');
        /*
        '<div class="{0}" width="420" height="315" src="{1}" frameborder="0" allowfullscreen>' +
                    '<a href="{2}">' + 
                        '<img class="{0}-thumbnail" src="{3}/Images/{4}/{5}#image-{4}" />' + 
                    '</a>' + 
                '</div>',
        */
                
        if (!empty($this->href)) {
            $img = array(
                'tag ' => 'a',
                'href' => $this->href,
                'cn' => array(
                    $img
                )
            );
        }
        
        
        if (!empty(strlen($this->video_url))) {
            $img = array(
                'tag' => 'div',
                'cls' => $this->cls,
                'frameborder' => 0,
                'allowfullscreen' => true,
                'width' => 420,  // these are for video tricks - that we replace the outer
                'height' => 315,
                'src' => $this->video_url,
                'cn' => array(
                    $img
                )
            );
        }
        // we remove caption totally if its hidden... - will delete data.. but otherwise we end up with fake caption
        $captionhtml = $this->caption_display == 'none' || !strlen($this->caption) ? '' : $this->caption;
        
  
        return  array(
            'tag' => 'figure',
            'data-block' => 'Figure',
            'data-width' => $this->width, 
            
            
            'style' => array(
                'display' => 'block',
                'float' =>  $this->align ,
                'max-width' =>  $this->align == 'center' ? '100% !important' : ($this->width + ' !important'),
                'width' => $this->align == 'center' ? '100%' : $this->width,
                'margin' =>  '0px',
                'padding' => $this->align == 'center' ? '0' : '0 10px' ,
                'text-align' => $this->align   // seems to work for email..
                
            ),
           
            
            'align' => $this->align,
            'cn' => array(
                $img,
              
                array (
                    'tag'=> 'figcaption',
                    'data-display' => $this->caption_display,
                    'style' => array(
                        'text-align' => 'left',
                        'font-size' => '16px',
                        'line-height' => '24px',
                        'display' => $this->caption_display,
                        'max-width' => ($this->align == 'center' ?  $this->width : '100%' ) . ' !important',
                        'margin'=> $m,
                        'width'=> $this->align == 'center' ?  $this->width : '100%' 
                    
                         
                    ),
                    'cls' => strlen($this->cls) > 0 ? ($this->cls  + '-thumbnail' ) : '',
                    'cn' => array(
                        array(
                            'tag' => 'div',
                            'style'  => array(
                                'margin-top' => '16px',
                                'text-align' => 'left'
                            ),
                            'align'=> 'left',
                            'cn' => array(
                                array( 
                                    // we can not rely on yahoo syndication to use CSS elements - so have to use  '<i>' to encase stuff.
                                    'tag' => 'i',
                                    'html' => $captionhtml
                                )
                                
                            )
                        )
                        
                    )
                    
                )
            )
        );
         
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