<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
//
// Compile-time Flexy plugin — omit editor-only attributes from XTemplate output.
//

/**
 * tag hook for HTML_Template_Flexy_Compiler_XTemplate.
 */
class HTML_Template_Flexy_Plugin_StripAttributes
{
    /**
     * @param array $tag  element AST node (type, tag, attrs, children)
     * @return array
     */
    function tag($tag)
    {
        if (empty($tag['attrs']) || !is_array($tag['attrs'])) {
            return $tag;
        }

        foreach (array_keys($tag['attrs']) as $name) {
            if (strtolower($name) === 'contenteditable') {
                unset($tag['attrs'][$name]);
            }
        }

        return $tag;
    }
}
