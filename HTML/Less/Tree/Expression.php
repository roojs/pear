<?php

require_once 'HTML/Less/Tree.php';

/**
 * Expression
 *
 * @package Less
 * @subpackage tree
 */
class HTML_Less_Tree_Expression extends HTML_Less_Tree {

    public $value = array();
    public $parens = false;
    public $parensInOp = false;
    public $type = 'Expression';

    public function __construct($value, $parens = null) {
        $this->value = $value;
        $this->parens = $parens;
    }

    public function accept($visitor) {
        $this->value = $visitor->visitArray($this->value);
    }

    public function compile($env) {

        $doubleParen = false;

        require_once 'HTML/Less/Environment.php';
        
        if ($this->parens && !$this->parensInOp) {
            HTML_Less_Environment::$parensStack++;
        }

        $returnValue = null;
        if ($this->value) {

            $count = count($this->value);

            if ($count > 1) {

                $ret = array();
                foreach ($this->value as $e) {
                    $ret[] = $e->compile($env);
                }
                $returnValue = new HTML_Less_Tree_Expression($ret);
            } else {

                if (($this->value[0] instanceof HTML_Less_Tree_Expression) && $this->value[0]->parens && !$this->value[0]->parensInOp) {
                    $doubleParen = true;
                }

                $returnValue = $this->value[0]->compile($env);
            }
        } else {
            $returnValue = $this;
        }

        if ($this->parens) {
            if (!$this->parensInOp) {
                HTML_Less_Environment::$parensStack--;
            } elseif (!HTML_Less_Environment::isMathOn() && !$doubleParen) {
                require_once 'HTML/Less/Tree/Paren.php';
                $returnValue = new HTML_Less_Tree_Paren($returnValue);
            }
        }
        return $returnValue;
    }

    /**
     * @see HTML_Less_Tree::genCSS
     */
    public function genCSS($output) {
        $val_len = count($this->value);
        for ($i = 0; $i < $val_len; $i++) {
            $this->value[$i]->genCSS($output);
            if ($i + 1 < $val_len) {
                $output->add(' ');
            }
        }
    }

    public function throwAwayComments() {

        if (is_array($this->value)) {
            $new_value = array();
            foreach ($this->value as $v) {
                if ($v instanceof HTML_Less_Tree_Comment) {
                    continue;
                }
                $new_value[] = $v;
            }
            $this->value = $new_value;
        }
    }

}
