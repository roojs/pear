<?php

class File_DXF
{

    public $sections;
    
    // File_DXF_SectionHeader
	public $header;

    // File_DXF_SectionClasses
	public $classes;

    // File_DXF_SectionTables
	public $tables;

    // File_DXF_SectionBlocks
	public $blocks;

    // File_DXF_SectionEntites
	public $entities;

    // File_DXF_SectionObjects
	public $objects;	
	
	// File_DXF_SectionThumbnailImage
	public $thumbnailImage;
	
	function __construct($readPath = false)
	{
	    $this->sections = array(
	        'header',
			'classes',
			'tables',
			'blocks',
			'entities',
			'objects',
			'thumbnailImage',
		);
		
		$this->header = self::factory('SectionHeader');
		$this->header = self::factory('SectionClasses');
		$this->header = self::factory('SectionTables');
		$this->header = self::factory('SectionBlocks');
		$this->header = self::factory('SectionEntities');
		$this->header = self::factory('SectionObjects');
		$this->header = self::factory('SectionThumbnailImage');
		
		$this->addBasicObjects();
		
		if ($readPath) {
			$this->read($readPath);
		}
	}
	
	static function factory($type, $cfg=array())
	{
	    $cls = 'File_DXF_'.$type;
	    if (!class_exists($cls)) {
    	    require_once 'File/DXF/'. $type .'.php';
	    }
	    return new $cls($cfg);
    }
	
	function addBasicObjects()
	{
	
	    $this->header->addItem(self::factory('SystemVariable', 
	        array(
	            'name' => "acadver",
	            'values' => array(1 => "AC1012"),
            )
        ));
		$this->header->addItem(self::factory('SystemVariable',
		    array(
		        'name' => "dwgcodepage",
		        'values' => array(3 => "ANSI_1252"),
	        )
        ));
		$this->header->addItem(self::factory('SystemVariable',
		    array(
		        'name' => "insbase", 
		        'values' => array('point' => array(0, 0, 0)),
	        )
        ));
		$this->header->addItem(self::factory('SystemVariable',
		    array(
		        'name' => "extmin", 
		        'values' => array('point' => array(0, 0, 0)),
	        )
        ));
		$this->header->addItem(self::factory('SystemVariable',
		    array(
		        'name' => "extmax", 
		        'values' => array('point' => array(0, 0, 0)),
	        )
        ));


		$tables = array();
		$tableOrder = array('VPORT', 'LTYPE', 'LAYER', 'STYLE', 'VIEW', 'UCS', 'APPID', 'DIMSTYLE', 'BLOCK_RECORD');
		
		foreach ($tableOrder as $table) {
			$tables[$table] = self::factory('Table', array('name' => $table));
		}
		$tables['APPID']->addEntry(self::factory('AppID', array('name' => 'ACAD')));
		
		$this->addBlock($tables, '*model_space');
		$this->addBlock($tables, '*paper_space');

		$tables['LAYER']->addEntry(self::factory('Layer', array('name' => '0')));

		$tables['LTYPE']->addEntry(self::factory('LType', array('name' => 'byblock')));
		$tables['LTYPE']->addEntry(self::factory('LType', array('name' => 'bylayer')));

		$tables['STYLE']->addEntry(self::factory('Style', array('name' =>'standard')));

		$this->tables->addMultipleItems($tables);

		$this->objects->addItem(self::factory('Dictionary', array('entries' => array('ACAD_GROUP'))));
	}

	function addBlock(&$tables, $name)
	{
		$tables['BLOCK_RECORD']->addEntry(self::factory('BlockRecord', array('name' => $name)));
		$this->blocks->addItem(self::factory('Block', array('name', $name)));
	}
	
	// File handle
	public $handle; 

	function read($path, $opts= array())
	{
	    if (!file_exists($path) || !filesize($path)) {
			print_r($path);
	        die('ERROR The path to the file is either invalid or the file is empty');
        }
        
        $this->handle = fopen($path, 'r');
        
        while ($pair = $this->readPair($this->handle)) {
            
            if ($pair['key'] != 0 || $pair['value'] != 'SECTION') {
			    // Got invalid starting tag for a new section
			    print_r($pair);
			    die("ERROR got invalid starting tag for a new section");
		    }
		    // Beginning of a new section
		    
		    $sectionTypePair = $this->readPair($this->handle);
		    
		    if($sectionTypePair['key'] != 2){
			    // Got invalid group code for a section name
			    print_R($sectionTypePair['key']);
			    die("ERROR got invalid group code for a section name");
		    }
		    
		    switch ($sectionTypePair['value']) {
		        case 'HEADER':
		            $this->header->parse($this);
		            if (!empty($opts['ignore_header'])) {
		                $this->header = false;
	                }
	                break;
				case 'CLASSES':
					$this->classes->parse($this);
                    break;
                case 'BLOCKS':
                    $this->blocks->parse($this);
                    break;
/**
 *
 * Alan's comment
 * this may contain filenames (xref)
 * if block_only  - 
 * 		reutnr here..
 *		.. close file..
 *
 */                    
                case 'ENTITIES':
                    $this->entities->parse($this, $opts);
				    break;
			    case 'OBJECTS':
				    $this->objects->parse();
				    break;
		        case 'THUMBNAILIMAGE':
		            $this->thumbnailImage->parse($this);
		            break;
				default:
				    print_R($sectionTypePair['value']);
				    die("ERROR got unknown section name");
					break;
			}
			
		}
		
		fclose($this->handle);
	}
	
	function readPair(){
		$key = fgets($this->handle);
		$value = fgets($this->handle);
		return array(
			'key' => trim($key),
			'value' => trim($value),
		);
	}
	
	/**
	 *
	 * TODO ENHANCE / CHECK THE CODE BLOEW
	 *
	 */

	function addEntity($entity)
	{
		$this->entities->addItem($entity);
	}
	 
	function addMultipleEntities($entities)
	{
		foreach ($entities as $entity) {
			$this->entities->addItem($entity);
		}
	}

	/**
	 * Handler to add a table item to the DXFighter instance
	 * @param $item
	 */
	function addTable($tableItem)
	{
		require_once 'File/DXF/Table.php';

		$table = self::factory('Table', array('name' => (((new ReflectionClass($tableItem))->getShortName()))));
		$table->addEntry($tableItem);
		$this->tables->addItem($table);
	}

	/**
	 * Public function to load a DXF file and add all entities to the DXF object
	 * @param string $path a file path to the DXF file to read
	 * @param array $move Vector to move all entities with
	 * @param int $rotate a degree value to rotate all entities with
	 */
	function addEntitiesFromFile($path, $move = [0, 0, 0], $rotate = 0)
	{
		$this->read($path, $move, $rotate);
	}

	/**
	 * Public function to move all entities on a DXF File
	 * @param array $move vector to move the entity with
	 */
	function move($move)
	{
		foreach ($this->entities->getItems() as $entity) {
			if (method_exists($entity, 'move')) {
				$entity->move($move);
			} else {
				echo 'The ' . get_class($entity) . ' class does not have a move function.' . PHP_EOL;
			}
		}
	}


	/**
	 * Public function to rotate all entities on a DXF File
	 * @param int $rotate degree value used for the rotation
	 * @param array $rotationCenter center point of the rotation
	 */
	function rotate($rotate, $rotationCenter = array(0, 0, 0))
	{
		foreach ($this->entities->getItems() as $entity) {
			if (method_exists($entity, 'rotate')) {
				$entity->rotate($rotate, $rotationCenter);
			} else {
				echo 'The ' . get_class($entity) . ' class does not have a rotate function.' . PHP_EOL;
			}
		}
	}

	/**
	 * Outputs an array representation of the DXF
	 *
	 * @return array
	 */
	function toArray()
	{
		$output = array();
		foreach ($this->sections as $section) {
			$output[strtoupper($section)] = $this->{$section}->toArray();
		}
		return $output;
	}

	/**
	 * Returns or outputs the DXF as a string
	 *
	 * @param bool|TRUE $return
	 * @return string
	 */
	function toString($return = TRUE)
	{
		$output = array();
		array_push($output, 999, "DXFighter");
		foreach ($this->sections as $section) {
			$output[] = $this->{$section}->render();
		}
		array_push($output, 0, "EOF");
		$outputString = implode(PHP_EOL, $output);

		if ($return) {
			echo nl2br($outputString);
			return '';
		} else {
			return $outputString;
		}
	}

	/**
	 * Save the DXF to a specific place
	 *
	 * @param $fileName
	 * @return $absolutePath
	 */
	function saveAs($fileName)
	{
		$fh = fopen($fileName, 'w');
		fwrite($fh, iconv("UTF-8", "WINDOWS-1252", $this->toString(FALSE)));
		fclose($fh);
		return realpath($fileName);
	}	
}
