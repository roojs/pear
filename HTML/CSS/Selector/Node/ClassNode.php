<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
require_once 'HTML/CSS/Selector/XPathExpr.php';
require_once 'HTML/CSS/Selector/Node/NodeInterface.php';

/**
 * ClassNode represents a "selector.className" node.
 *
 * This component is a port of the Python lxml library,
 * which is copyright Infrae and distributed under the BSD license.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class HTML_CSS_Selector_Node_ClassNode implements HTML_CSS_Selector_Node_NodeInterface
{
    protected $selector;
    protected $className;

    /**
     * The constructor.
     *
     * @param NodeInterface $selector  The XPath Selector
     * @param string        $className The class name
     */
    public function __construct($selector, $className)
    {
        $this->selector = $selector;
        $this->className = $className;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return sprintf('%s[%s.%s]', __CLASS__, $this->selector, $this->className);
    }

    /**
     * {@inheritDoc}
     */
    public function toXpath()
    {
        $selXpath = $this->selector->toXpath();
        $selXpath->addCondition(sprintf("contains(concat(' ', normalize-space(@class), ' '), %s)", HTML_CSS_Selector_XPathExpr::xpathLiteral(' '.$this->className.' ')));

        return $selXpath;
    }
}
