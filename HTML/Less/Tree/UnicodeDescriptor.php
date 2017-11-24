<?php

require_once 'HTML/Less/Tree.php';

/**
 * UnicodeDescriptor
 *
 * @package Less
 * @subpackage tree
 */
class HTML_Less_Tree_UnicodeDescriptor extends HTML_Less_Tree {

    public $value;
    public $type = 'UnicodeDescriptor';

    public function __construct($value) {
        $this->value = $value;
    }

    /**
     * @see HTML_Less_Tree::genCSS
     */
    public function genCSS($output) {
        $output->add($this->value);
    }

    public function compile() {
        return $this;
    }

}
