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
 * OrNode represents a "Or" node.
 *
 * This component is a port of the Python lxml library,
 * which is copyright Infrae and distributed under the BSD license.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class HTML_CSS_Selector_Node_OrNode implements HTML_CSS_Selector_Node_NodeInterface
{
    /**
     * @var NodeInterface[]
     */
    protected $items;

    /**
     * Constructor.
     *
     * @param NodeInterface[] $items An array of NodeInterface objects
     */
    public function __construct($items)
    {
        $this->items = $items;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return sprintf('%s(%s)', __CLASS__, $this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function toXpath()
    {
        $paths = array();
        foreach ($this->items as $item) {
            $paths[] = $item->toXpath();
        }

        return new HTML_CSS_Selector_XPathExprOr($paths);
    }
}
