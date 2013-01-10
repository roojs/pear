<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
include_once dirname(__FILE__) . '/../XPathExpr.php';
include_once dirname(__FILE__) . '/NodeInterface.php';

/**
 * HashNode represents a "selector#id" node.
 *
 * This component is a port of the Python lxml library,
 * which is copyright Infrae and distributed under the BSD license.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class HTML_CSS_CssSelector_Node_HashNode implements HTML_CSS_CssSelector_Node_NodeInterface
{
    protected $selector;
    protected $id;

    /**
     * Constructor.
     *
     * @param NodeInterface $selector The NodeInterface object
     * @param string        $id       The ID
     */
    public function __construct($selector, $id)
    {
        $this->selector = $selector;
        $this->id = $id;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return sprintf('%s[%s#%s]', __CLASS__, $this->selector, $this->id);
    }

    /**
     * {@inheritDoc}
     */
    public function toXpath()
    {
        $path = $this->selector->toXpath();
        $path->addCondition(sprintf('@id = %s', HTML_CSS_CssSelector_XPathExpr::xpathLiteral($this->id)));

        return $path;
    }
}
