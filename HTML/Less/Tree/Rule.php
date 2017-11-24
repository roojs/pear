<?php

require_once 'HTML/Less/Environment.php';
require_once 'HTML/Less/Output.php';
require_once 'HTML/Less/Parser.php';
require_once 'HTML/Less/Tree.php';

require_once 'HTML/Less/Exception/Compiler.php';
require_once 'HTML/Less/Tree/Value.php';
require_once 'HTML/Less/Tree/Ruleset.php';
require_once 'HTML/Less/Tree/Keyword.php';

/**
 * Rule
 *
 * @package Less
 * @subpackage tree
 */
class HTML_Less_Tree_Rule extends HTML_Less_Tree {

    public $name;
    public $value;
    public $important;
    public $merge;
    public $index;
    public $inline;
    public $variable;
    public $currentFileInfo;
    public $type = 'Rule';

    /**
     * @param string $important
     */
    public function __construct($name, $value = null, $important = null, $merge = null, $index = null, $currentFileInfo = null, $inline = false) {
        $this->name = $name;
        $this->value = ($value instanceof HTML_Less_Tree_Value || $value instanceof HTML_Less_Tree_Ruleset) ? $value : new HTML_Less_Tree_Value(array($value));
        $this->important = $important ? ' ' . trim($important) : '';
        $this->merge = $merge;
        $this->index = $index;
        $this->currentFileInfo = $currentFileInfo;
        $this->inline = $inline;
        $this->variable = ( is_string($name) && $name[0] === '@');
    }

    public function accept($visitor) {
        $this->value = $visitor->visitObj($this->value);
    }

    /**
     * @see HTML_Less_Tree::genCSS
     */
    public function genCSS($output) {

        $output->add($this->name . HTML_Less_Environment::$_outputMap[': '], $this->currentFileInfo, $this->index);
        try {
            $this->value->genCSS($output);
        } catch (HTML_Less_Exception_Parser $e) {
            $e->index = $this->index;
            $e->currentFile = $this->currentFileInfo;
            throw $e;
        }
        $output->add($this->important . (($this->inline || (HTML_Less_Environment::$lastRule && HTML_Less_Parser::$options['compress'])) ? "" : ";"), $this->currentFileInfo, $this->index);
    }

    public function compile($env) {

        $name = $this->name;
        if (is_array($name)) {
            // expand 'primitive' name directly to get
            // things faster (~10% for benchmark.less):
            if (count($name) === 1 && $name[0] instanceof HTML_Less_Tree_Keyword) {
                $name = $name[0]->value;
            } else {
                $name = $this->CompileName($env, $name);
            }
        }

        $strictMathBypass = HTML_Less_Parser::$options['strictMath'];
        if ($name === "font" && !HTML_Less_Parser::$options['strictMath']) {
            HTML_Less_Parser::$options['strictMath'] = true;
        }

        try {
            $evaldValue = $this->value->compile($env);

            if (!$this->variable && $evaldValue->type === "DetachedRuleset") {
                throw new HTML_Less_Exception_Compiler("Rulesets cannot be evaluated on a property.", null, $this->index, $this->currentFileInfo);
            }

            if (HTML_Less_Environment::$mixin_stack) {
                $return = new HTML_Less_Tree_Rule($name, $evaldValue, $this->important, $this->merge, $this->index, $this->currentFileInfo, $this->inline);
            } else {
                $this->name = $name;
                $this->value = $evaldValue;
                $return = $this;
            }
        } catch (HTML_Less_Exception_Parser $e) {
            if (!is_numeric($e->index)) {
                $e->index = $this->index;
                $e->currentFile = $this->currentFileInfo;
            }
            throw $e;
        }

        HTML_Less_Parser::$options['strictMath'] = $strictMathBypass;

        return $return;
    }

    public function CompileName($env, $name) {
        $output = new HTML_Less_Output();
        foreach ($name as $n) {
            $n->compile($env)->genCSS($output);
        }
        return $output->toString();
    }

    public function makeImportant() {
        return new HTML_Less_Tree_Rule($this->name, $this->value, '!important', $this->merge, $this->index, $this->currentFileInfo, $this->inline);
    }

}
