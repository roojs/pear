<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Alan Knowles <alan@akbkhome.com>                            |
// +----------------------------------------------------------------------+
//
//  XTemplate compiler backend — Roo XTemplate syntax to compiled PHP.
//

require_once 'HTML/Template/Flexy/Compiler.php';

/**
 * Compile XTemplate markup to PHP for HTML_Template_Flexy::bufferedOutputObject($t).
 *
 * Structure: DOMDocument walk (&lt;tpl&gt;, HTML elements). Fields: regex in text nodes.
 */
class HTML_Template_Flexy_Compiler_XTemplate extends HTML_Template_Flexy_Compiler
{
    /**
     * @var object HTML_Template_Flexy
     */
    var $flexy;

    /**
     * Compile an XTemplate file or string.
     *
     * @param object HTML_Template_Flexy $flexy
     * @param string|false              $string  source string, or false to read currentTemplate
     * @return bool|string|object        true, compiled PHP string, or PEAR_Error
     */
    function compile($flexy, $string = false)
    {
        $this->flexy = $flexy;

        if ($string === false) {
            $string = file_get_contents($flexy->currentTemplate);
        }

        $php = $this->compileSource($string);
        if ($this->is_a($php, 'PEAR_Error')) {
            return $php;
        }

        if ($flexy->options['compileToString']) {
            return $php;
        }

        $cfp = fopen($flexy->compiledTemplate, 'w');
        if (!$cfp) {
            return HTML_Template_Flexy::staticRaiseError(
                'HTML_Template_Flexy::failed to write to ' . $flexy->compiledTemplate,
                HTML_TEMPLATE_FLEXY_ERROR_FILE,
                HTML_TEMPLATE_FLEXY_ERROR_RETURN
            );
        }

        fwrite($cfp, $php);
        fclose($cfp);
        chmod($flexy->compiledTemplate, 0775);
        clearstatcache();

        $mtime = filemtime($flexy->currentTemplate);
        touch($flexy->compiledTemplate, $mtime);

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($flexy->compiledTemplate, true);
        }

        return true;
    }

    /**
     * Parse XTemplate source and return compiled PHP.
     *
     * @param string $data  template source
     * @return string|object  PHP source or PEAR_Error
     */
    function compileSource($data)
    {
        $ast = $this->parseTemplate($data);
        if ($this->is_a($ast, 'PEAR_Error')) {
            return $ast;
        }

        $php = $this->emitNodes(
            $ast,
            array(array('item' => '$t', 'parent' => 'null'))
        );
        if ($this->is_a($php, 'PEAR_Error')) {
            return $php;
        }

        return $php;
    }

    /**
     * Parse XTemplate source string into an AST via DOM.
     *
     * @param string $source
     * @return array|object
     */
    function parseTemplate($source)
    {
        $dom = new DOMDocument();
        $wrapped = '<xtemplate-root>' . $source . '</xtemplate-root>';
        $prevErrors = libxml_use_internal_errors(true);
        $ok = $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $wrapped,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($prevErrors);
        if (!$ok) {
            return HTML_Template_Flexy::staticRaiseError(
                'XTemplate: DOM load failed',
                HTML_TEMPLATE_FLEXY_ERROR_SYNTAX,
                HTML_TEMPLATE_FLEXY_ERROR_RETURN
            );
        }

        return $this->parseNodes($dom->documentElement);
    }

    /**
     * Walk DOM child nodes into AST nodes.
     *
     * @param DOMNode $parent
     * @return array|object
     */
    function parseNodes($parent)
    {
        $nodes = array();
        foreach ($parent->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
                $literal = $this->parseLiteral($child->nodeValue);
                if ($literal !== false) {
                    $nodes[] = $literal;
                }
                continue;
            }

            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            if (strtolower($child->nodeName) === 'tpl') {
                $node = $this->parseTplNode($child);
                if ($this->is_a($node, 'PEAR_Error')) {
                    return $node;
                }
                $nodes[] = $node;
                continue;
            }

            $node = $this->parseElementNode($child);
            if ($this->is_a($node, 'PEAR_Error')) {
                return $node;
            }
            $nodes[] = $node;
        }

        return $nodes;
    }

    /**
     * Parse a text node value into a literal AST node, or false if empty.
     *
     * @param string $text
     * @return array|false
     */
    function parseLiteral($text)
    {
        if ($text === '') {
            return false;
        }

        $parts = array();
        $re = '/(\{[\w\-\.]+(?:\:[\w\.]+(?:\([^)]*\))?)?\})/';
        $bits = preg_split($re, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        if (!is_array($bits)) {
            $bits = array($text);
        }
        foreach ($bits as $bit) {
            if ($bit !== '' && $bit[0] === '{'
                && preg_match('/^\{([\w\-\.]+)(?:\:([\w\.]+)(?:\((.*?)\))?)?\}$/', $bit, $m)
            ) {
                $parts[] = array(
                    'name' => $m[1],
                    'mod' => isset($m[2]) ? $m[2] : '',
                    'args' => isset($m[3]) ? $m[3] : '',
                );
                continue;
            }
            if ($bit !== '') {
                if (trim($bit) === '') {
                    continue;
                }
                $parts[] = $bit;
            }
        }

        if (!count($parts)) {
            return false;
        }

        return array(
            'type' => 'literal', 
            'parts' => $parts
        );
    }

    /**
     * Parse a &lt;tpl&gt; element into a loop or if AST node.
     *
     * @param DOMElement $child
     * @return array|object
     */
    function parseTplNode($child)
    {
        $for = $child->getAttribute('for');
        if ($for !== '') {
            $children = $this->parseNodes($child);
            if ($this->is_a($children, 'PEAR_Error')) {
                return $children;
            }
            return array(
                'type'     => 'loop',
                'for'      => $for,
                'children' => $children,
            );
        }

        $if = $child->getAttribute('if');
        if ($if !== '') {
            $children = $this->parseNodes($child);
            if ($this->is_a($children, 'PEAR_Error')) {
                return $children;
            }
            return array(
                'type'     => 'if',
                'expr'     => $if,
                'children' => $children,
            );
        }

        return HTML_Template_Flexy::staticRaiseError(
            'XTemplate: <tpl> needs for= or if=',
            HTML_TEMPLATE_FLEXY_ERROR_SYNTAX,
            HTML_TEMPLATE_FLEXY_ERROR_RETURN
        );
    }

    /**
     * Parse an HTML element into an element AST node.
     *
     * @param DOMElement $child
     * @return array|object
     */
    function parseElementNode($child)
    {
        $attrs = array();
        foreach ($child->attributes as $attr) {
            // libxml percent-encodes { } in URL-ish attrs (href/src) before we see them
            $attrs[$attr->name] = rawurldecode($attr->value);
        }

        $children = $this->parseNodes($child);
        if ($this->is_a($children, 'PEAR_Error')) {
            return $children;
        }

        $node = array(
            'type'     => 'element',
            'tag'      => $child->nodeName,
            'attrs'    => $attrs,
            'children' => $children,
        );
        $node = $this->flexy->plugin('tag', $node);

        return $node;
    }

    /**
     * Emit compiled PHP for an AST node list.
     *
     * @param array $nodes
     * @param array $scopes  stack of array('item'=>'$values','parent'=>'$t')
     * @return string
     */
    function emitNodes($nodes, $scopes)
    {
        if (!is_array($nodes) || !count($nodes)) {
            return '';
        }

        $scope = $scopes[count($scopes) - 1];
        $chunks = array();

        foreach ($nodes as $node) {
            $chunk = $this->emitNode($node, $scopes, $scope);
            if ($this->is_a($chunk, 'PEAR_Error')) {
                return $chunk;
            }
            $chunks[] = $chunk;
        }

        return implode('', $chunks);
    }

    /**
     * Emit compiled PHP for one AST node.
     *
     * @param array $node
     * @param array $scopes
     * @param array $scope
     * @return string
     */
    function emitNode($node, $scopes, $scope)
    {
        if ($node['type'] === 'element') {
            list($open, $close) = $this->elementTags($node, $scope);
            $children = $this->emitNodes($node['children'], $scopes);
            if ($this->is_a($children, 'PEAR_Error')) {
                return $children;
            }

            return $open . $children . $close;
        }

        if ($node['type'] === 'literal') {
            return $this->emitLiteral($node, $scope);
        }

        if ($node['type'] === 'loop') {
            $for = $node['for'];
            $var = '$' . $for;
            $item = $scope['item'];
            $scopes[] = array(
                'item' => $var, 
                'parent' => '$parent'
            );
            $body = $this->emitNodes($node['children'], $scopes);
            array_pop($scopes);
            if ($this->is_a($body, 'PEAR_Error')) {
                return $body;
            }

            return "<?php foreach ({$item}->{$for} as {$var}) { \$parent = {$item}; if (is_array({$var})) { {$var} = (object) {$var}; } ?>{$body}<?php } ?>";
        }

        if ($node['type'] !== 'if') {
            return '';
        }

        $cond = $this->emitIfExpr($node['expr'], $scope);
        if ($this->is_a($cond, 'PEAR_Error')) {
            return $cond;
        }
        $body = $this->emitNodes($node['children'], $scopes);
        if ($this->is_a($body, 'PEAR_Error')) {
            return $body;
        }

        return "<?php if ({$cond}) { ?>{$body}<?php } ?>";
    }

    function emitIfExpr($expr, $scope)
    {
        $expr = trim($expr);

        // tags_list.length
        if (substr($expr, -7) === '.length') {
            $field = substr($expr, 0, -7);
            if (preg_match('/^\w+$/', $field)) {
                return 'count(' . $this->emitFieldPath($field, $scope) . ')';
            }
        }

        // ontable == 'core_project'
        $eq = strpos($expr, '==');
        if ($eq !== false) {
            $field = trim(substr($expr, 0, $eq));
            $literal = trim(substr($expr, $eq + 2));
            if (preg_match('/^\w+$/', $field)
                && strlen($literal) >= 2
                && $literal[0] === "'"
                && substr($literal, -1) === "'"
            ) {
                return $this->emitFieldPath($field, $scope) . ' == ' . $literal;
            }
        }

        return HTML_Template_Flexy::staticRaiseError(
            'XTemplate: unsupported if expression: ' . $expr,
            HTML_TEMPLATE_FLEXY_ERROR_SYNTAX,
            HTML_TEMPLATE_FLEXY_ERROR_RETURN
        );
    }

    /**
     * XTemplate field path → PHP object chain for current scope.
     *
     * @param string $path  e.g. cms_name, parent.baseURL
     * @param array  $scope
     * @return string
     */
    function emitFieldPath($path, $scope)
    {
        $segs = explode('.', $path);
        if ($segs[0] === 'parent') {
            $php = '$parent';
            array_shift($segs);
        } else {
            $php = $scope['item'];
        }
        foreach ($segs as $seg) {
            $php = "{$php}->{$seg}";
        }

        return $php;
    }

    /**
     * Build open and close tag strings for an element node.
     * Attribute values may contain {fields} (same split / emit as text nodes).
     *
     * @param array $node
     * @param array $scope
     * @return array  [open, close]
     */
    function elementTags($node, $scope)
    {
        $attrs = array();
        foreach ($node['attrs'] as $name => $value) {
            if (strpos($value, '{') === false) {
                $attrs[] = $name . '="' . htmlspecialchars($value, ENT_QUOTES) . '"';
                continue;
            }
            $attrs[] = $name . '="'
                . $this->emitLiteral($this->parseLiteral($value), $scope) . '"';
        }

        $attrStr = count($attrs) ? ' ' . implode(' ', $attrs) : '';
        $tag = $node['tag'];

        return array("<{$tag}{$attrStr}>", "</{$tag}>");
    }

    /**
     * Literal parts: string chunks of text, or array('name'=>, 'mod'=>) for {fields}.
     *
     * @param array $node
     * @param array $scope
     * @return string
     */
    function emitLiteral($node, $scope)
    {
        $out = '';

        foreach ($node['parts'] as $part) {
            if (is_string($part)) {
                $out .= $part;
                continue;
            }

            $php = $this->emitFieldPath($part['name'], $scope);
            if ($part['mod'] === 'raw') {
                $out .= "<?= {$php} ?>";
                continue;
            }
            if ($part['mod'] === 'date') {
                $fmt = empty($part['args']) ? 'j M Y' : trim($part['args'], "\"'");
                $out .= '<?= (($__d = strtotime(' . $php . ')) ? date('
                    . var_export($fmt, true) . ', $__d) : \'\') ?>';
                continue;
            }
            $out .= "<?= htmlspecialchars({$php}) ?>";
        }

        return $out;
    }
}
