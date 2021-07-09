<?php
require_once 'File/DXF/Circle.php';

class File_DXF_Arc extends File_DXF_Circle
{

  /*
   * OLD CODE BELOW
   */

  // public $start;
  // public $end;

  /**
   * Arc constructor.
   * @param $point
   * @param $radius
   * @param int $start
   * @param int $end
   * @param int $thickness
   * @param array $extrusion
   */
  /*
  function __construct($point, $radius, $start, $end, $thickness = 0, $extrusion = array(0, 0, 1))
  {
    parent::__construct($point, $radius, $thickness, $extrusion);
    $this->entityType = 'arc';
    $this->start = $start;
    $this->end = $end;
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
    $output = array();
    array_push($output, parent::render());
    array_push($output, 100, 'AcDbArc');
    array_push($output, 50, $this->start);
    array_push($output, 51, $this->end);
    return implode(PHP_EOL, $output);
  }

  public function getStart()
  {
    return $this->start;
  }

  public function getEnd()
  {
    return $this->end;
  }
  */
}
