<?php

require_once 'HTML/Less/Tree.php';

/**
 * Negative
 *
 * @package Less
 * @subpackage tree
 */
class HTML_Less_Tree_Negative extends HTML_Less_Tree {

    public $value;
    public $type = 'Negative';

    public function __construct($node) {
        $this->value = $node;
    }

    //function accept($visitor) {
    //	$this->value = $visitor->visit($this->value);
    //}

    /**
     * @see HTML_Less_Tree::genCSS
     */
    public function genCSS($output) {
        $output->add('-');
        $this->value->genCSS($output);
    }

    public function compile($env) {
        
        require_once 'HTML/Less/Environment.php';
        
        if (HTML_Less_Environment::isMathOn()) {
            
            require_once 'HTML/Less/Tree/Operation.php';
            require_once 'HTML/Less/Tree/Dimension.php';
            
            $ret = new HTML_Less_Tree_Operation('*', array(new HTML_Less_Tree_Dimension(-1), $this->value));
            return $ret->compile($env);
        }
        return new HTML_Less_Tree_Negative($this->value->compile($env));
    }

}
