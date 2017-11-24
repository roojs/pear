<?php

require_once 'HTML/Less/Tree.php';

require_once 'HTML/Less/Exception/Compiler.php';

/**
 * Keyword
 *
 * @package Less
 * @subpackage tree
 */
class HTML_Less_Tree_Keyword extends HTML_Less_Tree {

    public $value;
    public $type = 'Keyword';

    /**
     * @param string $value
     */
    public function __construct($value) {
        $this->value = $value;
    }

    public function compile() {
        return $this;
    }

    /**
     * @see HTML_Less_Tree::genCSS
     */
    public function genCSS($output) {

        if ($this->value === '%') {
            throw new HTML_Less_Exception_Compiler("Invalid % without number");
        }

        $output->add($this->value);
    }

    public function compare($other) {
        if ($other instanceof HTML_Less_Tree_Keyword) {
            return $other->value === $this->value ? 0 : 1;
        } else {
            return -1;
        }
    }

}
