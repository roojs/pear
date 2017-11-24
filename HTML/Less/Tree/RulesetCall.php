<?php

require_once 'HTML/Less/Tree.php';
require_once 'HTML/Less/Tree/Variable.php';

/**
 * RulesetCall
 *
 * @package Less
 * @subpackage tree
 */
class HTML_Less_Tree_RulesetCall extends HTML_Less_Tree {

    public $variable;
    public $type = "RulesetCall";

    public function __construct($variable) {
        $this->variable = $variable;
    }

    public function accept($visitor) {
        
    }

    public function compile($env) {
        $variable = new HTML_Less_Tree_Variable($this->variable);
        $detachedRuleset = $variable->compile($env);
        return $detachedRuleset->callEval($env);
    }

}
