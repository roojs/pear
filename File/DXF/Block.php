<?php

require_once 'File/DXF/BasicObject.php';

class File_DXF_Block extends File_DXF_BasicObject
{

    /*
     * OLD CODE BELOW
     */

    // protected $name;
    // protected $xrefPath;
    // protected $endblk;
    // protected $point = [0, 0, 0];
    // protected $entities = [];
  
    /**
     * Block constructor.
     * @param $name
     */
    /*
    function __construct($name) {
      $this->entityType = 'block';
      $this->name = $name;
      $this->flags = array_fill(0, 6, 0);
      $this->pointer = $this->getUniqueID();
      parent::__construct();
      $this->endblk = new Endblk($this->layer, $this->pointer);
    }
  
    public function getName() {
      return $this->name;
    }
  
    public function getEntities() {
      return $this->entities;
    }
    */
  
    /**
     * Adds an Entity to the block
     *
     * @param Entity $entity
     */
    /*
    public function addEntity(Entity $entity) {
      $this->entities[] = $entity;
    }
    */
  
    /**
     * Public function to render an entity, returns a string representation of
     * the entity.
     * @return string
     */
    /*
    public function render() {
      $output = parent::render();
      array_push($output, 100, 'AcDbBlockBegin');
      array_push($output, 2, strtoupper($this->name));
      array_push($output, 70, $this->flagsToString());
      array_push($output, $this->point($this->point));
      array_push($output, 3, strtoupper($this->name));
      array_push($output, 1, $this->xrefPath);
      foreach($this->entities as $entity) {
        array_push($output, $entity->render());
      }
      array_push($output, $this->endblk->render());
      return implode(PHP_EOL, $output);
    }
    */
}
