<?php

/**
 * This is used in the HTML editor to make content editable
 *
 * In our case it's used to render images and tables correctly.
 */

Roo.htmleditor.Block  = function(cfg)
{
    // do nothing .. should not be called really.
}
/**
 * factory method to get the block from an element (using cache if necessary)
 * @static
 * @param {HtmlElement} the dom element
 */
Roo.htmleditor.Block.factory = function(node)
{
    var cc = Roo.htmleditor.Block.cache;
    var id = Roo.get(node).id;
    if (typeof(cc[id]) != 'undefined' && (!cc[id].node || cc[id].node.closest('body'))) {
        Roo.htmleditor.Block.cache[id].readElement(node);
        return Roo.htmleditor.Block.cache[id];
    }
    var db  = node.getAttribute('data-block');
    if (!db) {
        db = node.nodeName.toLowerCase().toUpperCaseFirst();
    }
    var cls = Roo.htmleditor['Block' + db];
    if (typeof(cls) == 'undefined') {
        //Roo.log(node.getAttribute('data-block'));
        Roo.log("OOps missing block : " + 'Block' + db);
        return false;
    }
    Roo.htmleditor.Block.cache[id] = new cls({ node: node });
    return Roo.htmleditor.Block.cache[id];  /// should trigger update element
};

/**
 * initalize all Elements from content that are 'blockable'
 * @static
 * @param the body element
 */
Roo.htmleditor.Block.initAll = function(body, type)
{
    if (typeof(type) == 'undefined') {
        var ia = Roo.htmleditor.Block.initAll;
        ia(body,'table');
        ia(body,'td');
        ia(body,'figure');
        return;
    }
    Roo.each(Roo.get(body).query(type), function(e) {
        Roo.htmleditor.Block.factory(e);    
    },this);
};
// question goes here... do we need to clear out this cache sometimes?
// or show we make it relivant to the htmleditor.
Roo.htmleditor.Block.cache = {};

Roo.htmleditor.Block.prototype = {
    
    node : false,
    
     // used by context menu
    friendly_name : 'Based Block',
    
    // text for button to delete this element
    deleteTitle : false,
    
    context : false,
    /**
     * Update a node with values from this object
     * @param {DomElement} node
     */
    updateElement : function(node)
    {
        Roo.DomHelper.update(node === undefined ? this.node : node, this.toObject());
    },
     /**
     * convert to plain HTML for calling insertAtCursor..
     */
    toHTML : function()
    {
        return Roo.DomHelper.markup(this.toObject());
    },
    /**
     * used by readEleemnt to extract data from a node
     * may need improving as it's pretty basic
     
     * @param {DomElement} node
     * @param {String} tag - tag to find, eg. IMG ?? might be better to use DomQuery ?
     * @param {String} attribute (use html - for contents, style for using next param as style, or false to return the node)
     * @param {String} style the style property - eg. text-align
     */
    getVal : function(node, tag, attr, style)
    {
        var n = node;
        if (tag !== true && n.tagName != tag.toUpperCase()) {
            // in theory we could do figure[3] << 3rd figure? or some more complex search..?
            // but kiss for now.
            n = node.getElementsByTagName(tag).item(0);
        }
        if (!n) {
            return '';
        }
        if (attr === false) {
            return n;
        }
        if (attr == 'html') {
            return n.innerHTML;
        }
        if (attr == 'style') {
            return n.style[style]; 
        }
        
        return n.hasAttribute(attr) ? n.getAttribute(attr) : '';
            
    },
    /**
     * create a DomHelper friendly object - for use with 
     * Roo.DomHelper.markup / overwrite / etc..
     * (override this)
     */
    toObject : function()
    {
        return {};
    },
      /**
     * Read a node that has a 'data-block' property - and extract the values from it.
     * @param {DomElement} node - the node
     */
    readElement : function(node)
    {
        
    } 
    
    
};
