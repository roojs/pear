<?php

require_once 'File/DXF/BasicObject.php';

// const POINTITEMLABEL = 'point' ;

class File_DXF_SystemVariable extends File_DXF_BasicObject
{

    function parse($dxf) {
        $this->skipParseVariable($dxf);
    }

    /*
     * OLD CODE BELOW
     */ 
    
    // protected $variable;
    // protected $values;

    /**
     * SystemVariable constructor.
     * @param $variable
     * @param $values
     */
    /*
    function __construct($variable, $values) {
        $this->variable = $variable;
        $this->values = $values;
        parent::__construct();
    }

    public function getName() {
        return $this->variable;
    }

    public function getValues() {
        return $this->values;
    }
    */

    /**
     * Public function to render an entity, returns a string representation of
     * the entity.
     * @return string
     */
    /*
    public function render() {
        $output = array();
        array_push($output, 9, "$" . strtoupper($this->variable));
        if (isset($this->values[POINTITEMLABEL])) {
            array_push($output, $this->point($this->values[POINTITEMLABEL]));
            unset($this->values[POINTITEMLABEL]);
        }
        foreach ($this->values as $groupCode => $value) {
            array_push($output, $groupCode, $value);
        }
        return implode(PHP_EOL, $output);
    }
    */

}
