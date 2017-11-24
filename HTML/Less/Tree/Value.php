<?php

require_once 'HTML/Less/Tree.php';
require_once 'HTML/Less/Environment.php';

/**
 * Value
 *
 * @package Less
 * @subpackage tree
 */
class HTML_Less_Tree_Value extends HTML_Less_Tree {

    public $type = 'Value';
    public $value;

    public function __construct($value) {
        $this->value = $value;
    }

    public function accept($visitor) {
        $this->value = $visitor->visitArray($this->value);
    }

    public function compile($env) {

        $ret = array();
        $i = 0;
        foreach ($this->value as $i => $v) {
            $ret[] = $v->compile($env);
        }
        if ($i > 0) {
            return new HTML_Less_Tree_Value($ret);
        }
        return $ret[0];
    }

    /**
     * @see HTML_Less_Tree::genCSS
     */
    function genCSS($output) {
        $len = count($this->value);
        for ($i = 0; $i < $len; $i++) {
            $this->value[$i]->genCSS($output);
            if ($i + 1 < $len) {
                $output->add(HTML_Less_Environment::$_outputMap[',']);
            }
        }
    }

}
