<?php

require_once 'File/DXF/Entity.php';

class File_DXF_Block extends File_DXF_Entity
{
    public $entities = array();

    function parse($dxf)
    {
        // parse common pair for entities
        $this->parseCommon($dxf);
        while($pair = $dxf->readPair()) {

            switch($pair['key']) { 
                case 0:
                    // Beginning of a new entity

                    switch($pair['value']) {
                        case 'ENDBLK':
                            // No more entities
                            // End of this block
                            $this->skipParseEntity($dxf);
                            return;
                        case 'INSERT':
                            $entity = $dxf->factory('Insert');
                            $entity->parse($dxf);
                            $this->entities[] = $entity;
                            break;			
                        case 'ATTRIB':
                        case 'SEQEND': 
                        case '3DFACE': 
                        case '3DSOLID': 
                        case 'ACAD_PROXY_ENTITY': 
                        case 'ARC': 
                        case 'ATTDEF':  
                        case 'BODY':
                        case 'CIRCLE': 
                        case 'DIMENSION': 
                        case 'ELLIPSE': 
                        case 'HATCH': 
                        case 'HELIX': 
                        case 'IMAGE':
                        case 'LEADER':
                        case 'LIGHT': 
                        case 'LINE': 
                        case 'LWPOLYLINE':
                        case 'MESH': 
                        case 'MLINE': 
                        case 'MLEADERSTYLE'; 
                        case 'MLEADER':
                        case 'MTEXT': 
                        case 'OLEFRAME': 
                        case 'OLE2FRAME': 
                        case 'POINT': 
                        case 'POLYLINE': 
                        case 'RAY': 
                        case 'REGION': 
                        case 'SECTION': 
                        case 'SHAPE': 
                        case 'SOLID': 
                        case 'SPLINE':
                        case 'SUN':
                        case 'SURFACE':
                        case 'TABLE':
                        case 'TEXT': 
                        case 'TOLERANCE': 
                        case 'TRACE': 
                        case 'UNDERLAY':
                        case 'VERTEX': 
                        case 'VIEWPORT': 
                        case 'WIPEOUT': 
                        case'XLINE':
                            // skip parsing other entities
                            $this->skipParseEntity($dxf);
                            break;
                        default:
                            $pairString = implode(", ", $pair);
                            throw new Exception ("Got unknown pair within an entity BLOCK ($pairString)");
                            break;
                    }
                    break;
                case 100:
                    // Beginning of a subclass
                    $dxf->factory($pair['value'])->parseToEntity($dxf, $this);
                    break;
                case 1001:
                    $this->skipParseExtendedData($dxf);
                    break;
                default:
                    $pairString = implode(", ", $pair); 
                    throw new Exception ("Got unknown pair for entity BLOCK ($pairString)");
                    break;
            }
        }
    }

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
