<?php
/**
 * SCSSPHP
 *
 * @copyright 2012-2018 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://leafo.github.io/scssphp
 */
 
/**
 * Base node
 *
 * @author Anthon Pang <anthon.pang@gmail.com>
 */
abstract class HTML_Scss_Node
{
    /**
     * @var string
     */
    public $type;

    /**
     * @var integer
     */
    public $sourceIndex;

    /**
     * @var integer
     */
    public $sourceLine;

    /**
     * @var integer
     */
    public $sourceColumn;
}
