<?php

class File_DXF
{
	protected $sections;

	/**
	 * @var File_DXF_SectionHeader
	 */
	protected $header;

	/**
	 * @var File_DXF_SectionClasses
	 */
	protected $classes;

	/**
	 * @var File_DXF_SectionTables
	 */
	protected $tables;

	/**
	 * @var Section
	 */
	protected $blocks;

	/**
	 * @var Section
	 */
	protected $entities;

	/**
	 * @var Section
	 */
	protected $objects;
	protected $thumbnailImage;


	/**
	 * DXFighter constructor.
	 * sets basic values needed for further usage if the init flag is set
	 *
	 * @param string|bool $readPath
	 */
	function __construct($readPath = false)
	{

		$this->sections = array(
			'header',
			'classes',
			'tables',
			'blocks',
			'entities',
			'objects',
			'thumbnailImage'
		);

		require_once 'File/DXF/Section.php';


		$this->addBasicObjects();
		if ($readPath) {
			$this->read($readPath);
		}
	}

	/**
	 * Private function, called while constructing a new object of this class.
	 * As DXF files have to fit certain requirements we need all these basic items.
	 */
	private function addBasicObjects()
	{
		require_once 'File/DXF/SystemVariable.php';
		require_once 'File/DXF/Table.php';
		require_once 'File/DXF/AppID.php';
		require_once 'File/DXF/Layer.php';
		require_once 'File/DXF/LType.php';
		require_once 'File/DXF/Style.php';
		require_once 'File/DXF/Dictionary.php';

		$this->header->addItem(new File_DXF_SystemVariable("acadver", array(1 => "AC1012")));
		$this->header->addItem(new File_DXF_SystemVariable("dwgcodepage", array(3 => "ANSI_1252")));
		$this->header->addItem(new File_DXF_SystemVariable("insbase", array('point' => array(0, 0, 0))));
		$this->header->addItem(new File_DXF_SystemVariable("extmin", array('point' => array(0, 0, 0))));
		$this->header->addItem(new File_DXF_SystemVariable("extmax", array('point' => array(0, 0, 0))));

		$tables = array();
		$tableOrder = array('vport', 'ltype', 'layer', 'style', 'view', 'ucs', 'appid', 'dimstyle', 'block_record');
		foreach ($tableOrder as $table) {
			$tables[$table] = new File_DXF_Table($table);
		}
		$tables['appid']->addEntry(new File_DXF_AppID('ACAD'));

		$this->addBlock($tables, '*model_space');
		$this->addBlock($tables, '*paper_space');

		$tables['layer']->addEntry(new File_DXF_Layer('0'));

		$tables['ltype']->addEntry(new File_DXF_LType('byblock'));
		$tables['ltype']->addEntry(new File_DXF_LType('bylayer'));

		$tables['style']->addEntry(new File_DXF_Style('standard'));
		$this->tables->addMultipleItems($tables);

		$this->objects->addItem(new File_DXF_Dictionary(array('ACAD_GROUP')));
	}

	/**
	 * Handler for adding block entities to the DXF file
	 * @param $tables
	 * @param $name
	 */
	public function addBlock(&$tables, $name)
	{
		require_once 'File/DXF/BlockRecord.php';
		require_once 'File/DXF/Block.php';
		$tables['block_record']->addEntry(new File_DXF_BlockRecord($name));
		$this->blocks->addItem(new File_DXF_Block($name));
	}

	/**
	 * Handler to add an entity to the DXFighter instance
	 * @param $entity
	 */
	public function addEntity($entity)
	{
		$this->entities->addItem($entity);
	}

	/**
	 * Handler to add multiple entities to the DXFighter instance
	 * @param $entities array
	 */
	public function addMultipleEntities($entities)
	{
		foreach ($entities as $entity) {
			$this->entities->addItem($entity);
		}
	}

	/**
	 * Handler to add a table item to the DXFighter instance
	 * @param $item
	 */
	public function addTable($tableItem)
	{
		require_once 'File/DXF/Table.php';

		$table = new File_DXF_Table(((new ReflectionClass($tableItem))->getShortName()));
		$table->addEntry($tableItem);
		$this->tables->addItem($table);
	}

	/**
	 * Public function to load a DXF file and add all entities to the DXF object
	 * @param string $path a file path to the DXF file to read
	 * @param array $move Vector to move all entities with
	 * @param int $rotate a degree value to rotate all entities with
	 */
	public function addEntitiesFromFile($path, $move = [0, 0, 0], $rotate = 0)
	{
		$this->read($path, $move, $rotate);
	}

	public function getHeader()
	{
		return $this->header;
	}

	public function getClasses()
	{
		return $this->classes;
	}

	public function getTables()
	{
		return $this->tables;
	}

	public function getBlocks()
	{
		return $this->blocks;
	}

	public function getObject()
	{
		return $this->objects;
	}

	public function getEntities()
	{
		return $this->entities->getItems();
	}

	/**
	 * Public function to move all entities on a DXF File
	 * @param array $move vector to move the entity with
	 */
	public function move($move)
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
	public function rotate($rotate, $rotationCenter = array(0, 0, 0))
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
	public function toArray()
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
	public function toString($return = TRUE)
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
	public function saveAs($fileName)
	{
		$fh = fopen($fileName, 'w');
		fwrite($fh, iconv("UTF-8", "WINDOWS-1252", $this->toString(FALSE)));
		fclose($fh);
		return realpath($fileName);
	}

	private function read($path, $move = [0, 0, 0], $rotate = 0)
	{
		if (!file_exists($path) || !filesize($path)) {
			throw new Exception('The path to the file is either invalid or the file is empty');
		}

		$handle = fopen($path, 'r');
		while ($pair = $this->readPair($handle)) {
			if ($pair['key'] == 0 && $pair['value'] == 'SECTION') {
				//Start a new Section

				$sectionTypePair = $this->readPair($handle);

				if($sectionTypePair['key'] == 2){
					switch ($sectionTypePair['value']) {
						case 'HEADER':
							require_once 'File/DXF/SectionHeader.php';
							$this->header = new File_DXF_SectionHeader();
							$this->header->parse($handle);
							break;
						case 'TABLES':
							require_once 'File/DXF/SectionTables.php';
							$this->tables = new File_DXF_SectionTables();
							$this->tables->parse($handle);
							break;
						case 'BLOCKS':
							require_once 'File/DXF/SectionBlocks.php';
							$this->blocks = new File_DXF_SectionBlocks();
							$this->blocks->parse($handle);
							break;
						case 'ENTITIES':
							require_once 'File/DXF/SectionEntities.php';
							$this->entities = new File_DXF_SectionEntities();
							$this->entities->parse($handle, true, $move, $rotate);
							break;
						case 'OBJECTS':
							require_once 'File/DXF/SectionObjects.php';
							$this->objects = new File_DXF_SectionObjects();
							$this->objects->parse();
							break;
					}
				}
			}
		}
		fclose($handle);


	}

	private function readPair($handle){
		$key = fgets($handle);
		$value = fgets($handle);

		return array(
			'key' => trim($key),
			'value' => trim($value)
		);
	}
}
