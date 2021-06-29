<?php

/**
 * Created by PhpStorm.
 * User: jpietler
 * Date: 30.12.16
 * Time: 16:18
 *
 * Dokumentation http://www.autodesk.com/techpubs/autocad/acad2000/dxf/polyline_dxf_06.htm
 * This is baed on DXF Fighter by - https://github.com/enjoping/DXFighter
 */


/**
 * Class Polyline
 * @package DXFighter\lib
 */
require_once 'File/DXF/Entity.php';

class File_DXF_Polyline extends File_DXF_Entity
{
    public $base = array(0, 0, 0);
    public $points = array();
    public $dimension;
    public $seqend;

    /**
     * Polyline constructor.
     * @param int $dimension
     */
    function __construct($dimension = 2)
    {
        $this->entityType = 'polyline';
        $this->flags = array_fill(0, 7, 0);
        $this->dimension = $dimension;
        parent::__construct();
    }

    /**
     * Public function to add a new point to the polyline
     * @param array $point
     * @param int $bulge
     */
    public function addPoint($point, $bulge = 0)
    {
        require_once 'File/DXF/Vertex.php';

        $this->points[] = new File_DXF_Vertex($point, $this->dimension, $this->handle, $this->layer, $bulge);
        return $this;
    }

    /**
     * Private function to add a sequenze end entity to the polyline
     */
    private function addSeqend()
    {
        require_once 'File/DXF/Seqend.php';

        $this->seqend = new File_DXF_Seqend($this->handle, $this->layer);
    }

    /**
     * Public function to move a Polyline entity
     * @param array $move vector to move the entity with
     */
    public function move($move)
    {
        foreach ($this->points as $point) {
            $point->move($move);
        }
    }

    /**
     * Public function to rotate all points of a polyline
     * @param int $rotate degree value used for the rotation
     * @param array $rotationCenter center point of the rotation
     */
    public function rotate($rotate, $rotationCenter = array(0, 0, 0))
    {
        foreach ($this->points as $point) {
            $point->rotate(deg2rad($rotate), $rotationCenter);
        }
    }

    /**
     * Public function to render an entity, returns a string representation of
     * the entity.
     * @return string
     */
    public function render()
    {
        if (!isset($this->seqend)) {
            $this->addSeqend();
        }
        $output = parent::render();
        array_push($output, 100, 'AcDb' . $this->dimension . 'dPolyline');
        array_push($output, $this->point($this->base));
        array_push($output, 70, $this->flagsToString());

        foreach ($this->points as $point) {
            array_push($output, $point->render());
        }

        array_push($output, $this->seqend->render());
        return implode(PHP_EOL, $output);
    }

    public function getBase()
    {
        return $this->base;
    }

    public function getPoints()
    {
        return $this->points;
    }

    public function getDimension()
    {
        return $this->dimension;
    }
}
