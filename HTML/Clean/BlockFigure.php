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
        )
        parent::__construct();
    }
    
Roo.htmleditor.BlockFigure = function(cfg)
{
    if (cfg.node) {
        this.readElement(cfg.node);
        this.updateElement(cfg.node);
    }
    Roo.apply(this, cfg);
}
Roo.extend(Roo.htmleditor.BlockFigure, Roo.htmleditor.Block, {
 
    
    // setable values.
    image_src: '',
    align: 'center',
    caption : '',
    caption_display : 'block',
    width : '100%',
    cls : '',
    href: '',
    video_url : '',
    
    // margin: '2%', not used
    
    text_align: 'left', //   (left|right) alignment for the text caption default left. - not used at present

    
    // used by context menu
    friendly_name : 'Image with caption',
    deleteTitle : "Delete Image and Caption",
    
    contextMenu : function(toolbar)
    {
        
        var block = function() {
            return Roo.htmleditor.Block.factory(toolbar.tb.selectedNode);
        };
        
        
        var rooui =  typeof(Roo.bootstrap) == 'undefined' ? Roo : Roo.bootstrap;
        
        var syncValue = toolbar.editorcore.syncValue;
        
        var fields = {};
        
        return [
             {
                xtype : 'TextItem',
                text : "Source: ",
                xns : rooui.Toolbar  //Boostrap?
            },
            {
                xtype : 'Button',
                text: 'Change Image URL',
                 
                listeners : {
                    click: function (btn, state)
                    {
                        var b = block();
                        
                        Roo.MessageBox.show({
                            title : "Image Source URL",
                            msg : "Enter the url for the image",
                            buttons: Roo.MessageBox.OKCANCEL,
                            fn: function(btn, val){
                                if (btn != 'ok') {
                                    return;
                                }
                                b.image_src = val;
                                b.updateElement();
                                syncValue();
                                toolbar.editorcore.onEditorEvent();
                            },
                            minWidth:250,
                            prompt:true,
                            //multiline: multiline,
                            modal : true,
                            value : b.image_src
                        });
                    }
                },
                xns : rooui.Toolbar
            },
         
            {
                xtype : 'Button',
                text: 'Change Link URL',
                 
                listeners : {
                    click: function (btn, state)
                    {
                        var b = block();
                        
                        Roo.MessageBox.show({
                            title : "Link URL",
                            msg : "Enter the url for the link - leave blank to have no link",
                            buttons: Roo.MessageBox.OKCANCEL,
                            fn: function(btn, val){
                                if (btn != 'ok') {
                                    return;
                                }
                                b.href = val;
                                b.updateElement();
                                syncValue();
                                toolbar.editorcore.onEditorEvent();
                            },
                            minWidth:250,
                            prompt:true,
                            //multiline: multiline,
                            modal : true,
                            value : b.href
                        });
                    }
                },
                xns : rooui.Toolbar
            },
            {
                xtype : 'Button',
                text: 'Show Video URL',
                 
                listeners : {
                    click: function (btn, state)
                    {
                        Roo.MessageBox.alert("Video URL",
                            block().video_url == '' ? 'This image is not linked ot a video' :
                                'The image is linked to: <a target="_new" href="' + block().video_url + '">' + block().video_url + '</a>');
                    }
                },
                xns : rooui.Toolbar
            },
            
            
            {
                xtype : 'TextItem',
                text : "Width: ",
                xns : rooui.Toolbar  //Boostrap?
            },
            {
                xtype : 'ComboBox',
                allowBlank : false,
                displayField : 'val',
                editable : true,
                listWidth : 100,
                triggerAction : 'all',
                typeAhead : true,
                valueField : 'val',
                width : 70,
                name : 'width',
                listeners : {
                    select : function (combo, r, index)
                    {
                        toolbar.editorcore.selectNode(toolbar.tb.selectedNode);
                        var b = block();
                        b.width = r.get('val');
                        b.updateElement();
                        syncValue();
                        toolbar.editorcore.onEditorEvent();
                    }
                },
                xns : rooui.form,
                store : {
                    xtype : 'SimpleStore',
                    data : [
                        ['100%'],
                        ['80%'],
                        ['50%'],
                        ['20%'],
                        ['10%']
                    ],
                    fields : [ 'val'],
                    xns : Roo.data
                }
            },
            {
                xtype : 'TextItem',
                text : "Align: ",
                xns : rooui.Toolbar  //Boostrap?
            },
            {
                xtype : 'ComboBox',
                allowBlank : false,
                displayField : 'val',
                editable : true,
                listWidth : 100,
                triggerAction : 'all',
                typeAhead : true,
                valueField : 'val',
                width : 70,
                name : 'align',
                listeners : {
                    select : function (combo, r, index)
                    {
                        toolbar.editorcore.selectNode(toolbar.tb.selectedNode);
                        var b = block();
                        b.align = r.get('val');
                        b.updateElement();
                        syncValue();
                        toolbar.editorcore.onEditorEvent();
                    }
                },
                xns : rooui.form,
                store : {
                    xtype : 'SimpleStore',
                    data : [
                        ['left'],
                        ['right'],
                        ['center']
                    ],
                    fields : [ 'val'],
                    xns : Roo.data
                }
            },
            
            
            {
                xtype : 'Button',
                text: 'Hide Caption',
                name : 'caption_display',
                pressed : false,
                enableToggle : true,
                setValue : function(v) {
                    // this trigger toggle.
                     
                    this.setText(v ? "Hide Caption" : "Show Caption");
                    this.setPressed(v != 'block');
                },
                listeners : {
                    toggle: function (btn, state)
                    {
                        var b  = block();
                        b.caption_display = b.caption_display == 'block' ? 'none' : 'block';
                        this.setText(b.caption_display == 'block' ? "Hide Caption" : "Show Caption");
                        b.updateElement();
                        syncValue();
                        toolbar.editorcore.selectNode(toolbar.tb.selectedNode);
                        toolbar.editorcore.onEditorEvent();
                    }
                },
                xns : rooui.Toolbar
            }
        ];
        
    },
    /**
     * create a DomHelper friendly object - for use with
     * Roo.DomHelper.markup / overwrite / etc..
     */
    toObject : function()
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
