<?php

require_once 'HTML/Less/Tree.php';

/**
 * Dimension
 *
 * @package Less
 * @subpackage tree
 */
class HTML_Less_Tree_Dimension extends HTML_Less_Tree {

    public $value;
    public $unit;
    public $type = 'Dimension';

    public function __construct($value, $unit = null) {
        $this->value = floatval($value);

        require_once 'HTML/Less/Tree/Unit.php';
        
        if ($unit && ($unit instanceof HTML_Less_Tree_Unit)) {
            $this->unit = $unit;
        } elseif ($unit) {
            $this->unit = new HTML_Less_Tree_Unit(array($unit));
        } else {
            $this->unit = new HTML_Less_Tree_Unit( );
        }
    }

    public function accept($visitor) {
        $this->unit = $visitor->visitObj($this->unit);
    }

    public function compile() {
        return $this;
    }

    public function toColor() {
        require_once 'HTML/Less/Tree/Color.php';
        return new HTML_Less_Tree_Color(array($this->value, $this->value, $this->value));
    }

    /**
     * @see HTML_Less_Tree::genCSS
     */
    public function genCSS($output) {

        require_once 'HTML/Less/Parser.php';
        
        if (HTML_Less_Parser::$options['strictUnits'] && !$this->unit->isSingular()) {
            require_once 'HTML/Less/Exception/Compiler.php';
            throw new HTML_Less_Exception_Compiler("Multiple units in dimension. Correct the units or use the unit function. Bad unit: " . $this->unit->toString());
        }

        require_once 'HTML/Less/Functions.php';
        
        $value = HTML_Less_Functions::fround($this->value);
        $strValue = (string) $value;

        if ($value !== 0 && $value < 0.000001 && $value > -0.000001) {
            // would be output 1e-6 etc.
            $strValue = number_format($strValue, 10);
            $strValue = preg_replace('/\.?0+$/', '', $strValue);
        }

        if (HTML_Less_Parser::$options['compress']) {
            // Zero values doesn't need a unit
            if ($value === 0 && $this->unit->isLength()) {
                $output->add($strValue);
                return $strValue;
            }

            // Float values doesn't need a leading zero
            if ($value > 0 && $value < 1 && $strValue[0] === '0') {
                $strValue = substr($strValue, 1);
            }
        }

        $output->add($strValue);
        $this->unit->genCSS($output);
    }

    public function __toString() {
        return $this->toCSS();
    }

    // In an operation between two Dimensions,
    // we default to the first Dimension's unit,
    // so `1px + 2em` will yield `3px`.

    /**
     * @param string $op
     */
    public function operate($op, $other) {
        
        require_once 'HTML/Less/Functions.php';
        
        $value = HTML_Less_Functions::operate($op, $this->value, $other->value);
        $unit = clone $this->unit;

        if ($op === '+' || $op === '-') {

            if (!$unit->numerator && !$unit->denominator) {
                $unit->numerator = $other->unit->numerator;
                $unit->denominator = $other->unit->denominator;
            } elseif (!$other->unit->numerator && !$other->unit->denominator) {
                // do nothing
            } else {
                $other = $other->convertTo($this->unit->usedUnits());
                
                require_once 'HTML/Less/Parser.php';
                
                if (HTML_Less_Parser::$options['strictUnits'] && $other->unit->toString() !== $unit->toCSS()) {
                    require_once 'HTML/Less/Exception/Compiler.php';
                    throw new HTML_Less_Exception_Compiler("Incompatible units. Change the units or use the unit function. Bad units: '" . $unit->toString() . "' and " . $other->unit->toString() . "'.");
                }

                $value = HTML_Less_Functions::operate($op, $this->value, $other->value);
            }
        } elseif ($op === '*') {
            $unit->numerator = array_merge($unit->numerator, $other->unit->numerator);
            $unit->denominator = array_merge($unit->denominator, $other->unit->denominator);
            sort($unit->numerator);
            sort($unit->denominator);
            $unit->cancel();
        } elseif ($op === '/') {
            $unit->numerator = array_merge($unit->numerator, $other->unit->denominator);
            $unit->denominator = array_merge($unit->denominator, $other->unit->numerator);
            sort($unit->numerator);
            sort($unit->denominator);
            $unit->cancel();
        }
        return new HTML_Less_Tree_Dimension($value, $unit);
    }

    public function compare($other) {
        if ($other instanceof HTML_Less_Tree_Dimension) {

            if ($this->unit->isEmpty() || $other->unit->isEmpty()) {
                $a = $this;
                $b = $other;
            } else {
                $a = $this->unify();
                $b = $other->unify();
                if ($a->unit->compare($b->unit) !== 0) {
                    return -1;
                }
            }
            $aValue = $a->value;
            $bValue = $b->value;

            if ($bValue > $aValue) {
                return -1;
            } elseif ($bValue < $aValue) {
                return 1;
            } else {
                return 0;
            }
        } else {
            return -1;
        }
    }

    public function unify() {
        return $this->convertTo(array('length' => 'px', 'duration' => 's', 'angle' => 'rad'));
    }

    public function convertTo($conversions) {
        $value = $this->value;
        $unit = clone $this->unit;

        require_once 'HTML/Less/Tree/UnitConversions.php';
        
        if (is_string($conversions)) {
            $derivedConversions = array();
            foreach (HTML_Less_Tree_UnitConversions::$groups as $i) {
                if (isset(HTML_Less_Tree_UnitConversions::${$i}[$conversions])) {
                    $derivedConversions = array($i => $conversions);
                }
            }
            $conversions = $derivedConversions;
        }


        foreach ($conversions as $groupName => $targetUnit) {
            $group = HTML_Less_Tree_UnitConversions::${$groupName};

            //numerator
            foreach ($unit->numerator as $i => $atomicUnit) {
                $atomicUnit = $unit->numerator[$i];
                if (!isset($group[$atomicUnit])) {
                    continue;
                }

                $value = $value * ($group[$atomicUnit] / $group[$targetUnit]);

                $unit->numerator[$i] = $targetUnit;
            }

            //denominator
            foreach ($unit->denominator as $i => $atomicUnit) {
                $atomicUnit = $unit->denominator[$i];
                if (!isset($group[$atomicUnit])) {
                    continue;
                }

                $value = $value / ($group[$atomicUnit] / $group[$targetUnit]);

                $unit->denominator[$i] = $targetUnit;
            }
        }

        $unit->cancel();

        return new HTML_Less_Tree_Dimension($value, $unit);
    }

}
