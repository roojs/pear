<?php

require_once 'HTML/Less/Tree.php';

/**
 * Unit
 *
 * @package Less
 * @subpackage tree
 */
class HTML_Less_Tree_Unit extends HTML_Less_Tree {

    var $numerator = array();
    var $denominator = array();
    public $backupUnit;
    public $type = 'Unit';

    public function __construct($numerator = array(), $denominator = array(), $backupUnit = null) {
        $this->numerator = $numerator;
        $this->denominator = $denominator;
        $this->backupUnit = $backupUnit;
    }

    public function __clone() {
        
    }

    /**
     * @see HTML_Less_Tree::genCSS
     */
    public function genCSS($output) {
        
        require_once 'HTML/Less/Parser.php';
        
        if ($this->numerator) {
            $output->add($this->numerator[0]);
        } elseif ($this->denominator) {
            $output->add($this->denominator[0]);
        } elseif (!HTML_Less_Parser::$options['strictUnits'] && $this->backupUnit) {
            $output->add($this->backupUnit);
            return;
        }
    }

    public function toString() {
        $returnStr = implode('*', $this->numerator);
        foreach ($this->denominator as $d) {
            $returnStr .= '/' . $d;
        }
        return $returnStr;
    }

    public function __toString() {
        return $this->toString();
    }

    /**
     * @param HTML_Less_Tree_Unit $other
     */
    public function compare($other) {
        return $this->is($other->toString()) ? 0 : -1;
    }

    public function is($unitString) {
        return $this->toString() === $unitString;
    }

    public function isLength() {
        $css = $this->toCSS();
        return !!preg_match('/px|em|%|in|cm|mm|pc|pt|ex/', $css);
    }

    public function isAngle() {
        require_once 'HTML/Less/Tree/UnitConversions.php';
        return isset(HTML_Less_Tree_UnitConversions::$angle[$this->toCSS()]);
    }

    public function isEmpty() {
        return !$this->numerator && !$this->denominator;
    }

    public function isSingular() {
        return count($this->numerator) <= 1 && !$this->denominator;
    }

    public function usedUnits() {
        $result = array();
        
        require_once 'HTML/Less/Tree/UnitConversions.php';
        
        foreach (HTML_Less_Tree_UnitConversions::$groups as $groupName) {
            $group = HTML_Less_Tree_UnitConversions::${$groupName};

            foreach ($this->numerator as $atomicUnit) {
                if (isset($group[$atomicUnit]) && !isset($result[$groupName])) {
                    $result[$groupName] = $atomicUnit;
                }
            }

            foreach ($this->denominator as $atomicUnit) {
                if (isset($group[$atomicUnit]) && !isset($result[$groupName])) {
                    $result[$groupName] = $atomicUnit;
                }
            }
        }

        return $result;
    }

    public function cancel() {
        $counter = array();
        $backup = null;

        foreach ($this->numerator as $atomicUnit) {
            if (!$backup) {
                $backup = $atomicUnit;
            }
            $counter[$atomicUnit] = ( isset($counter[$atomicUnit]) ? $counter[$atomicUnit] : 0) + 1;
        }

        foreach ($this->denominator as $atomicUnit) {
            if (!$backup) {
                $backup = $atomicUnit;
            }
            $counter[$atomicUnit] = ( isset($counter[$atomicUnit]) ? $counter[$atomicUnit] : 0) - 1;
        }

        $this->numerator = array();
        $this->denominator = array();

        foreach ($counter as $atomicUnit => $count) {
            if ($count > 0) {
                for ($i = 0; $i < $count; $i++) {
                    $this->numerator[] = $atomicUnit;
                }
            } elseif ($count < 0) {
                for ($i = 0; $i < -$count; $i++) {
                    $this->denominator[] = $atomicUnit;
                }
            }
        }

        if (!$this->numerator && !$this->denominator && $backup) {
            $this->backupUnit = $backup;
        }

        sort($this->numerator);
        sort($this->denominator);
    }

}
