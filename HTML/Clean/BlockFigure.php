<?php 

/**
 * This handles 'figure' tags (which is what img's are auto wrapped with.)
 */

 
require_once 'Block.php';
abstract class  HTML_Clean_BlockFigure extends HTML_Clean_Block
{
    
    function __construct($cfg) {
        if ($cfg['node']) {
            $this->readElement($cfg['node']);
            $this->updateElement($cfg['node']);
        }
        parent::__construct();
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
        
        var d = document.createElement('div');
        d.innerHTML = this.caption;
        
        var m = this.width != '100%' && this.align == 'center' ? '0 auto' : 0; 
        
        var iw = this.align == 'center' ? this.width : '100%';
        var img =   {
            tag : 'img',
            contenteditable : 'false',
            src : this.image_src,
            alt : d.innerText.replace(/\n/g, " ").replace(/\s+/g, ' ').trim(), // removeHTML and reduce spaces..
            style: {
                width : iw,
                maxWidth : iw + ' !important', // this is not getting rendered?
                margin : m  
                
            }
        };
        /*
        '<div class="{0}" width="420" height="315" src="{1}" frameborder="0" allowfullscreen>' +
                    '<a href="{2}">' + 
                        '<img class="{0}-thumbnail" src="{3}/Images/{4}/{5}#image-{4}" />' + 
                    '</a>' + 
                '</div>',
        */
                
        if (this.href.length > 0) {
            img = {
                tag : 'a',
                href: this.href,
                contenteditable : 'true',
                cn : [
                    img
                ]
            };
        }
        
        
        if (this.video_url.length > 0) {
            img = {
                tag : 'div',
                cls : this.cls,
                frameborder : 0,
                allowfullscreen : true,
                width : 420,  // these are for video tricks - that we replace the outer
                height : 315,
                src : this.video_url,
                cn : [
                    img
                ]
            };
        }
        // we remove caption totally if its hidden... - will delete data.. but otherwise we end up with fake caption
        var captionhtml = this.caption_display == 'none' ? '' : (this.caption.length ? this.caption : "Caption");
        
  
        var ret =   {
            tag: 'figure',
            'data-block' : 'Figure',
            'data-width' : this.width, 
            contenteditable : 'false',
            
            style : {
                display: 'block',
                float :  this.align ,
                maxWidth :  this.align == 'center' ? '100% !important' : (this.width + ' !important'),
                width : this.align == 'center' ? '100%' : this.width,
                margin:  '0px',
                padding: this.align == 'center' ? '0' : '0 10px' ,
                textAlign : this.align   // seems to work for email..
                
            },
           
            
            align : this.align,
            cn : [
                img,
              
                {
                    tag: 'figcaption',
                    'data-display' : this.caption_display,
                    style : {
                        textAlign : 'left',
                        fontSize : '16px',
                        lineHeight : '24px',
                        display : this.caption_display,
                        maxWidth : (this.align == 'center' ?  this.width : '100%' ) + ' !important',
                        margin: m,
                        width: this.align == 'center' ?  this.width : '100%' 
                    
                         
                    },
                    cls : this.cls.length > 0 ? (this.cls  + '-thumbnail' ) : '',
                    cn : [
                        {
                            tag: 'div',
                            style  : {
                                marginTop : '16px',
                                textAlign : 'left'
                            },
                            align: 'left',
                            cn : [
                                {
                                    // we can not rely on yahoo syndication to use CSS elements - so have to use  '<i>' to encase stuff.
                                    tag : 'i',
                                    contenteditable : true,
                                    html : captionhtml
                                }
                                
                            ]
                        }
                        
                    ]
                    
                }
            ]
        };
        return ret;
         
    },
    
    readElement : function(node)
    {
        // this should not really come from the link...
        this.video_url = this.getVal(node, 'div', 'src');
        this.cls = this.getVal(node, 'div', 'class');
        this.href = this.getVal(node, 'a', 'href');
        
        
        this.image_src = this.getVal(node, 'img', 'src');
         
        this.align = this.getVal(node, 'figure', 'align');
        var figcaption = this.getVal(node, 'figcaption', false);
        if (figcaption !== '') {
            this.caption = this.getVal(figcaption, 'i', 'html');
        }
        

        this.caption_display = this.getVal(node, 'figcaption', 'data-display');
        //this.text_align = this.getVal(node, 'figcaption', 'style','text-align');
        this.width = this.getVal(node, true, 'data-width');
        //this.margin = this.getVal(node, 'figure', 'style', 'margin');
        
    },
    removeNode : function()
    {
        return this.node;
    }
    
  
   
     
    
    
    
    
})
