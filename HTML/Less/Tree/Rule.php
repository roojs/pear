<?php

require_once 'HTML/Less/Tree.php';

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
        
        require_once 'HTML/Less/Tree/Value.php';
        
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

        require_once 'HTML/Less/Environment.php';
        require_once 'HTML/Less/Exception/Parser.php';
        
        $output->add($this->name . HTML_Less_Environment::$_outputMap[': '], $this->currentFileInfo, $this->index);
        try {
            $this->value->genCSS($output);
        } catch (HTML_Less_Exception_Parser $e) {
            $e->index = $this->index;
            $e->currentFile = $this->currentFileInfo;
            throw $e;
        }
        
        require_once 'HTML/Less/Parser.php';
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
        
        require_once 'HTML/Less/Parser.php';
        
        $strictMathBypass = HTML_Less_Parser::$options['strictMath'];
        if ($name === "font" && !HTML_Less_Parser::$options['strictMath']) {
            HTML_Less_Parser::$options['strictMath'] = true;
        }
        
        require_once 'HTML/Less/Exception/Parser.php';
        
        try {
            $evaldValue = $this->value->compile($env);

            if (!$this->variable && $evaldValue->type === "DetachedRuleset") {
                require_once 'HTML/Less/Exception/Compiler.php';
                throw new HTML_Less_Exception_Compiler("Rulesets cannot be evaluated on a property.", null, $this->index, $this->currentFileInfo);
            }
            
            require_once 'HTML/Less/Environment.php';
            
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
        
        require_once 'HTML/Less/Output.php';
        
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
