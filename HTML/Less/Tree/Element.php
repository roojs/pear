<?php

/**
 * Element
 *
 * @package Less
 * @subpackage tree
 */
class HTML_Less_Tree_Element extends HTML_Less_Tree {

    public $combinator = '';
    public $value = '';
    public $index;
    public $currentFileInfo;
    public $type = 'Element';
    public $value_is_object = false;

    public function __construct($combinator, $value, $index = null, $currentFileInfo = null) {

        $this->value = $value;
        $this->value_is_object = is_object($value);

        if ($combinator) {
            $this->combinator = $combinator;
        }

        $this->index = $index;
        $this->currentFileInfo = $currentFileInfo;
    }

    public function accept($visitor) {
        if ($this->value_is_object) { //object or string
            $this->value = $visitor->visitObj($this->value);
        }
    }

    public function compile($env) {

        if (HTML_Less_Environment::$mixin_stack) {
            return new HTML_Less_Tree_Element($this->combinator, ($this->value_is_object ? $this->value->compile($env) : $this->value), $this->index, $this->currentFileInfo);
        }

        if ($this->value_is_object) {
            $this->value = $this->value->compile($env);
        }

        return $this;
    }

    /**
     * @see HTML_Less_Tree::genCSS
     */
    public function genCSS($output) {
        $output->add($this->toCSS(), $this->currentFileInfo, $this->index);
    }

    public function toCSS() {

        if ($this->value_is_object) {
            $value = $this->value->toCSS();
        } else {
            $value = $this->value;
        }


        if ($value === '' && $this->combinator && $this->combinator === '&') {
            return '';
        }


        return HTML_Less_Environment::$_outputMap[$this->combinator] . $value;
    }

}
