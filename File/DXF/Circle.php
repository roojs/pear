<?php

require_once 'File/DXF/Entity.php';

class File_DXF_Circle extends File_DXF_Entity
{

    /*
     * OLD CODE BELOW
     */

	// var $entityType = 'circle';
    // public $thickness;
    // public $point;
    // public $radius;
    // public $extrusion;

    /**
     * Circle constructor.
     * @param $point
     * @param $radius
     * @param int $thickness
     * @param array $extrusion
     */
    /*
    function __construct($cfg)
    {
        parent::__construct($cfg);
    }
    */

    /**
     * Public function to move a Circle entity
     * @param array $move vector to move the entity with
     */
    /*
    public function move($move)
    {
        $this->movePoint($this->point, $move);
    }
    */

    /**
     * Public function to render an entity, returns a string representation of
     * the entity.
     * @return string
     */
    /*
    public function render()
    {
        $output = parent::render();
        array_push($output, 100, 'AcDbCircle');
        array_push($output, 39, $this->thickness);
        array_push($output, $this->point($this->point));
        array_push($output, 40, $this->radius);
        array_push($output, $this->point($this->extrusion, 200));
        return implode(PHP_EOL, $output);
    }

    public function getThickness()
    {
        return $this->thickness;
    }

    public function getPoint()
    {
        return $this->point;
    }

    public function getRadius()
    {
        return $this->radius;
    }

    public function getExtrusion()
    {
        return $this->extrusion;
    }
    */
}
