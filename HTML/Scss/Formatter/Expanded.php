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

require_once 'HTML/Scss/Formatter.php';
require_once 'HTML/Scss/Formatter/OutputBlock.php';
/**
 * Expanded formatter
 *
 * @author Leaf Corcoran <leafot@gmail.com>
 */
class HTML_Scss_Formatter_Expanded extends HTML_Scss_Formatter
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->indentLevel = 0;
        $this->indentChar = '  ';
        $this->break = "\n";
        $this->open = ' {';
        $this->close = '}';
        $this->tagSeparator = ', ';
        $this->assignSeparator = ': ';
        $this->keepSemicolons = true;
    }

    /**
     * {@inheritdoc}
     */
    protected function indentStr()
    {
        return str_repeat($this->indentChar, $this->indentLevel);
    }

    /**
     * {@inheritdoc}
     */
    protected function blockLines(HTML_Scss_Formatter_OutputBlock $block)
    {
        $inner = $this->indentStr();

        $glue = $this->break . $inner;

        foreach ($block->lines as $index => $line) {
            if (substr($line, 0, 2) === '/*') {
                $block->lines[$index] = preg_replace('/(\r|\n)+/', $glue, $line);
            }
        }

        $this->write($inner . implode($glue, $block->lines));

        if (empty($block->selectors) || ! empty($block->children)) {
            $this->write($this->break);
        }
    }
}
