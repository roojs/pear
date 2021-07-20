<?php

require_once 'File/DXF/Entity.php';

class File_DXF_Vertex extends File_DXF_Entity
{

    /*
     * OLD CODE BELOW
     */

    // public $dimension;
    // public $point;
    // public $bulge;

    /**
     * Vertex constructor.
     * @param $point
     * @param $dimension
     * @param $pointer
     * @param $layer
     * @param $bulge
     */
    /*
    function __construct($point, $dimension, $pointer, $layer, $bulge)
    {
        $this->entityType = 'vertex';
        $this->point = $point;
        $this->dimension = $dimension;
        $this->pointer = $pointer;
        $this->layer = $layer;
        $this->flags = array_fill(0, 7, 0);
        $this->bulge = $bulge;
        parent::__construct();
    }
    */

    /**
     * Public function to move a Polyline entity
     * @param array $move vector to move the entity with
     */
    /*
    public function move($move)
    {
        $this->movePoint($this->point, $move);
    }
    */

    /**
     * @param $angle
     * @param $center
     */
    /*
    public function rotate($angle, $center)
    {
        $this->rotatePoint($this->point, $center, $angle);
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
        array_push($output, 100, 'AcDbVertex');
        array_push($output, 100, $this->dimension === 2 ? 'AcDb2dVertex' : 'AcDb3dPolylineVertex');
        array_push($output, $this->point($this->point));
        array_push($output, 42, $this->bulge);
        array_push($output, 70, $this->flagsToString());
        return implode(PHP_EOL, $output);
    }

    public function getDimension()
    {
        return $this->dimension;
    }

    public function getPoint()
    {
        return $this->point;
    }

    public function getBulge()
    {
        return $this->bulge;
    }
    */
}
