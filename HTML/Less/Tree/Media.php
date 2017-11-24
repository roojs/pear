<?php

require_once 'HTML/Less/Tree.php';

/**
 * Media
 *
 * @package Less
 * @subpackage tree
 */
class HTML_Less_Tree_Media extends HTML_Less_Tree {

    public $features;
    public $rules;
    public $index;
    public $currentFileInfo;
    public $isReferenced;
    public $type = 'Media';

    public function __construct($value = array(), $features = array(), $index = null, $currentFileInfo = null) {

        $this->index = $index;
        $this->currentFileInfo = $currentFileInfo;

        $selectors = $this->emptySelectors();

        require_once 'HTML/Less/Tree/Value.php';
        $this->features = new HTML_Less_Tree_Value($features);

        require_once 'HTML/Less/Tree/Ruleset.php';
        $this->rules = array(new HTML_Less_Tree_Ruleset($selectors, $value));
        $this->rules[0]->allowImports = true;
    }

    public function accept($visitor) {
        $this->features = $visitor->visitObj($this->features);
        $this->rules = $visitor->visitArray($this->rules);
    }

    /**
     * @see HTML_Less_Tree::genCSS
     */
    public function genCSS($output) {

        $output->add('@media ', $this->currentFileInfo, $this->index);
        $this->features->genCSS($output);
        HTML_Less_Tree::outputRuleset($output, $this->rules);
    }

    public function compile($env) {

        $media = new HTML_Less_Tree_Media(array(), array(), $this->index, $this->currentFileInfo);

        $strictMathBypass = false;
        
        require_once 'HTML/Less/Parser.php';
        
        if (HTML_Less_Parser::$options['strictMath'] === false) {
            $strictMathBypass = true;
            HTML_Less_Parser::$options['strictMath'] = true;
        }

        $media->features = $this->features->compile($env);

        if ($strictMathBypass) {
            HTML_Less_Parser::$options['strictMath'] = false;
        }

        $env->mediaPath[] = $media;
        $env->mediaBlocks[] = $media;

        array_unshift($env->frames, $this->rules[0]);
        $media->rules = array($this->rules[0]->compile($env));
        array_shift($env->frames);

        array_pop($env->mediaPath);

        return !$env->mediaPath ? $media->compileTop($env) : $media->compileNested($env);
    }

    public function variable($name) {
        return $this->rules[0]->variable($name);
    }

    public function find($selector) {
        return $this->rules[0]->find($selector, $this);
    }

    public function emptySelectors() {
        
        require_once 'HTML/Less/Tree/Element.php';
        require_once 'HTML/Less/Tree/Selector.php';
        
        $el = new HTML_Less_Tree_Element('', '&', $this->index, $this->currentFileInfo);
        $sels = array(new HTML_Less_Tree_Selector(array($el), array(), null, $this->index, $this->currentFileInfo));
        $sels[0]->mediaEmpty = true;
        return $sels;
    }

    public function markReferenced() {
        $this->rules[0]->markReferenced();
        $this->isReferenced = true;
        HTML_Less_Tree::ReferencedArray($this->rules[0]->rules);
    }

    // evaltop
    public function compileTop($env) {
        $result = $this;

        if (count($env->mediaBlocks) > 1) {
            $selectors = $this->emptySelectors();
            
            require_once 'HTML/Less/Tree/Ruleset.php';
            $result = new HTML_Less_Tree_Ruleset($selectors, $env->mediaBlocks);
            $result->multiMedia = true;
        }

        $env->mediaBlocks = array();
        $env->mediaPath = array();

        return $result;
    }

    public function compileNested($env) {
        $path = array_merge($env->mediaPath, array($this));

        // Extract the media-query conditions separated with `,` (OR).
        foreach ($path as $key => $p) {
            $value = $p->features instanceof HTML_Less_Tree_Value ? $p->features->value : $p->features;
            $path[$key] = is_array($value) ? $value : array($value);
        }

        // Trace all permutations to generate the resulting media-query.
        //
		// (a, b and c) with nested (d, e) ->
        //	a and d
        //	a and e
        //	b and c and d
        //	b and c and e

        $permuted = $this->permute($path);
        $expressions = array();
        foreach ($permuted as $path) {

            for ($i = 0, $len = count($path); $i < $len; $i++) {
                $path[$i] = HTML_Less_Parser::is_method($path[$i], 'toCSS') ? $path[$i] : new HTML_Less_Tree_Anonymous($path[$i]);
            }

            for ($i = count($path) - 1; $i > 0; $i--) {
                array_splice($path, $i, 0, array(new HTML_Less_Tree_Anonymous('and')));
            }

            $expressions[] = new HTML_Less_Tree_Expression($path);
        }
        $this->features = new HTML_Less_Tree_Value($expressions);



        // Fake a tree-node that doesn't output anything.
        return new HTML_Less_Tree_Ruleset(array(), array());
    }

    public function permute($arr) {
        if (!$arr)
            return array();

        if (count($arr) == 1)
            return $arr[0];

        $result = array();
        $rest = $this->permute(array_slice($arr, 1));
        foreach ($rest as $r) {
            foreach ($arr[0] as $a) {
                $result[] = array_merge(
                        is_array($a) ? $a : array($a), is_array($r) ? $r : array($r)
                );
            }
        }

        return $result;
    }

    public function bubbleSelectors($selectors) {

        if (!$selectors)
            return;

        $this->rules = array(new HTML_Less_Tree_Ruleset($selectors, array($this->rules[0])));
    }

}
