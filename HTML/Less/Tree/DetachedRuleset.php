<?php

require_once 'HTML/Less/Tree.php';

require_once 'HTML/Less/Tree/DetachedRuleset.php';

/**
 * DetachedRuleset
 *
 * @package Less
 * @subpackage tree
 */
class HTML_Less_Tree_DetachedRuleset extends HTML_Less_Tree {

    public $ruleset;
    public $frames;
    public $type = 'DetachedRuleset';

    public function __construct($ruleset, $frames = null) {
        $this->ruleset = $ruleset;
        $this->frames = $frames;
    }

    public function accept($visitor) {
        $this->ruleset = $visitor->visitObj($this->ruleset);
    }

    public function compile($env) {
        if ($this->frames) {
            $frames = $this->frames;
        } else {
            $frames = $env->frames;
        }
        return new HTML_Less_Tree_DetachedRuleset($this->ruleset, $frames);
    }

    public function callEval($env) {
        if ($this->frames) {
            return $this->ruleset->compile($env->copyEvalEnv(array_merge($this->frames, $env->frames)));
        }
        return $this->ruleset->compile($env);
    }

}
