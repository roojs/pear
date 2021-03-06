<?php

/*
* This file is part of the Symfony package.
*
* (c) Fabien Potencier <fabien@symfony.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

require_once 'HTML/CSS/Selector/Node/OrNode.php';
require_once 'HTML/CSS/Selector/Node/CombinedSelectorNode.php';
require_once 'HTML/CSS/Selector/Node/ElementNode.php';
require_once 'HTML/CSS/Selector/Node/HashNode.php';
require_once 'HTML/CSS/Selector/Node/ClassNode.php';
require_once 'HTML/CSS/Selector/Node/FunctionNode.php';
require_once 'HTML/CSS/Selector/Node/PseudoNode.php';
require_once 'HTML/CSS/Selector/Node/AttribNode.php';
require_once 'HTML/CSS/Selector/Token/Tokenizer.php';
require_once 'HTML/CSS/Selector/Token/TokenStream.php';


/**
* CssSelector is the main entry point of the component and can convert CSS
* selectors to XPath expressions.
*
* $xpath = CssSelector::toXpath('h1.foo');
*
* This component is a port of the Python lxml library,
* which is copyright Infrae and distributed under the BSD license.
*
* @author Fabien Potencier <fabien@symfony.com>
*
* @api
*/
class HTML_CSS_Selector
{
    /**
* Translates a CSS expression to its XPath equivalent.
* Optionally, a prefix can be added to the resulting XPath
* expression with the $prefix parameter.
*
* @param mixed $cssExpr The CSS expression.
* @param string $prefix An optional prefix for the XPath expression.
*
* @return string
*
* @throws ParseException When got None for xpath expression
*
* @api
*/
    public static function toXPath($cssExpr, $prefix = 'descendant-or-self::')
    {
        if (is_string($cssExpr)) {
            if (!$cssExpr) {
                return $prefix.'*';
            }

            if (preg_match('#^\w+\s*$#u', $cssExpr, $match)) {
                return $prefix.trim($match[0]);
            }

            if (preg_match('~^(\w*)#(\w+)\s*$~u', $cssExpr, $match)) {
                return sprintf("%s%s[@id = '%s']", $prefix, $match[1] ? $match[1] : '*', $match[2]);
            }

            if (preg_match('#^(\w*)\.(\w+)\s*$#u', $cssExpr, $match)) {
                return sprintf("%s%s[contains(concat(' ', normalize-space(@class), ' '), ' %s ')]", $prefix, $match[1] ? $match[1] : '*', $match[2]);
            }

            $parser = new self();
            $cssExpr = $parser->parse($cssExpr);
        }

        $expr = $cssExpr->toXpath();

        // @codeCoverageIgnoreStart
        if (!$expr) {
            throw new Exception(sprintf('Got None for xpath expression from %s.', $cssExpr));
        }
        // @codeCoverageIgnoreEnd

        if ($prefix) {
            $expr->addPrefix($prefix);
        }

        return (string) $expr;
    }

    /**
* Parses an expression and returns the Node object that represents
* the parsed expression.
*
* @param string $string The expression to parse
*
* @return Node\NodeInterface
*
* @throws \Exception When tokenizer throws it while parsing
*/
    public function parse($string)
    {
        $tokenizer = new HTML_CSS_Selector_Token_Tokenizer();

        $stream = new HTML_CSS_Selector_Token_TokenStream($tokenizer->tokenize($string), $string);

        try {
            return $this->parseSelectorGroup($stream);
        } catch (Exception $e) {
            $class = get_class($e);

            throw new $class(sprintf('%s at %s -> %s', $e->getMessage(), implode($stream->getUsed(), ''), $stream->peek()), 0, $e);
        }
    }

    /**
* Parses a selector group contained in $stream and returns
* the Node object that represents the expression.
*
* @param TokenStream $stream The stream to parse.
*
* @return Node\NodeInterface
*/
    private function parseSelectorGroup($stream)
    {
        $result = array();
        while (true) {
            $result[] = $this->parseSelector($stream);
            if ($stream->peek() == ',') {
                $stream->next();
            } else {
                break;
            }
        }

        if (count($result) == 1) {
            return $result[0];
        }
        
        return new HTML_CSS_Selector_Node_OrNode($result);
    }

    /**
* Parses a selector contained in $stream and returns the Node
* object that represents it.
*
* @param TokenStream $stream The stream containing the selector.
*
* @return Node\NodeInterface
*
* @throws ParseException When expected selector but got something else
*/
    private function parseSelector($stream)
    {
        $result = $this->parseSimpleSelector($stream);

        while (true) {
            $peek = $stream->peek();
            if (',' == $peek || null === $peek) {
                return $result;
            } elseif (in_array($peek, array('+', '>', '~'))) {
                // A combinator
                $combinator = (string) $stream->next();

                // Ignore optional whitespace after a combinator
                while (' ' == $stream->peek()) {
                    $stream->next();
                }
            } else {
                $combinator = ' ';
            }
            $consumed = count($stream->getUsed());
            $nextSelector = $this->parseSimpleSelector($stream);
            if ($consumed == count($stream->getUsed())) {
                throw new Exception(sprintf("Expected selector, got '%s'", $stream->peek()));
            }

            $result = new HTML_CSS_Selector_Node_CombinedSelectorNode($result, $combinator, $nextSelector);
        }

        return $result;
    }

    /**
* Parses a simple selector (the current token) from $stream and returns
* the resulting Node object.
*
* @param TokenStream $stream The stream containing the selector.
*
* @return Node\NodeInterface
*
* @throws ParseException When expected symbol but got something else
*/
    private function parseSimpleSelector($stream)
    {
        $peek = $stream->peek();
        if (!$peek) {
            throw new Exception(sprintf("Expected symbol, got End of Stream"));
              
        }
        
        if ('*' != $peek && !$peek->isType('Symbol')) {
            $element = $namespace = '*';
        } else {
            $next = $stream->next();
            if ('*' != $next && !$next->isType('Symbol')) {
                throw new Exception(sprintf("Expected symbol, got '%s'", $next));
            }

            if ($stream->peek() == '|') {
                $namespace = $next;
                $stream->next();
                $element = $stream->next();
                if ('*' != $element && !$next->isType('Symbol')) {
                    throw new Exception(sprintf("Expected symbol, got '%s'", $next));
                }
            } else {
                $namespace = '*';
                $element = $next;
            }
        }

        $result = new HTML_CSS_Selector_Node_ElementNode($namespace, $element);
        $hasHash = false;
        while (true) {
            $peek = $stream->peek();
            if ('#' == $peek) {
                if ($hasHash) {
                    /* You can't have two hashes
(FIXME: is there some more general rule I'm missing?) */
                    // @codeCoverageIgnoreStart
                    break;
                    // @codeCoverageIgnoreEnd
                }
                $stream->next();
                $result = new HTML_CSS_Selector_Node_HashNode($result, $stream->next());
                $hasHash = true;

                continue;
            } elseif ('.' == $peek) {
                $stream->next();
                $result = new HTML_CSS_Selector_Node_ClassNode($result, $stream->next());

                continue;
            } elseif ('[' == $peek) {
                $stream->next();
                $result = $this->parseAttrib($result, $stream);
                $next = $stream->next();
                if (']' != $next) {
                    throw new Exception(sprintf("] expected, got '%s'", $next));
                }

                continue;
            } elseif (':' == $peek || '::' == $peek) {
                $type = $stream->next();
                $ident = $stream->next();
                if (!$ident || !$ident->isType('Symbol')) {
                    throw new Exception(sprintf("Expected symbol, got '%s'", $ident));
                }

                if ($stream->peek() == '(') {
                    $stream->next();
                    $peek = $stream->peek();
                    if ($peek->isType('String')) {
                        $selector = $stream->next();
                    } elseif ($peek->isType('Symbol') && is_int($peek)) {
                        $selector = intval($stream->next());
                    } else {
                        // FIXME: parseSimpleSelector, or selector, or...?
                        $selector = $this->parseSimpleSelector($stream);
                    }
                    $next = $stream->next();
                    if (')' != $next) {
                        throw new Exception(sprintf("Expected ')', got '%s' and '%s'", $next, $selector));
                    }

                    $result = new HTML_CSS_Selector_Node_FunctionNode($result, $type, $ident, $selector);
                } else {
                    $result = new HTML_CSS_Selector_Node_PseudoNode($result, $type, $ident);
                }

                continue;
            } else {
                if (' ' == $peek) {
                    $stream->next();
                }

                break;
            }
            // FIXME: not sure what "negation" is
        }

        return $result;
    }

    /**
* Parses an attribute from a selector contained in $stream and returns
* the resulting AttribNode object.
*
* @param Node\NodeInterface $selector The selector object whose attribute
* is to be parsed.
* @param TokenStream $stream The container token stream.
*
* @return Node\AttribNode
*
* @throws ParseException When encountered unexpected selector
*/
    private function parseAttrib($selector, $stream)
    {
        $attrib = $stream->next();
        if ($stream->peek() == '|') {
            $namespace = $attrib;
            $stream->next();
            $attrib = $stream->next();
        } else {
            $namespace = '*';
        }

        if ($stream->peek() == ']') {
            return new HTML_CSS_Selector_Node_AttribNode($selector, $namespace, $attrib, 'exists', null);
        }

        $op = $stream->next();
        if (!in_array($op, array('^=', '$=', '*=', '=', '~=', '|=', '!='))) {
            throw new Exception(sprintf("Operator expected, got '%s'", $op));
        }

        $value = $stream->next();
        if (!$value->isType('Symbol') && !$value->isType('String')) {
            throw new Exception(sprintf("Expected string or symbol, got '%s'", $value));
        }

        return new HTML_CSS_Selector_Node_AttribNode($selector, $namespace, $attrib, $op, $value);
    }
}