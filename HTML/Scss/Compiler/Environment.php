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
 * Compiler environment
 *
 * @author Anthon Pang <anthon.pang@gmail.com>
 */
class HTML_Scss_Compiler_Environment
{
    /**
     * @var \Leafo\ScssPhp\Block
     */
    public $block;

    /**
     * @var \Leafo\ScssPhp\Compiler\Environment
     */
    public $parent;

    /**
     * @var array
     */
    public $store;

    /**
     * @var integer
     */
    public $depth;
}
