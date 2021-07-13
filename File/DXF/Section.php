<?php

/* test-dxf.php (For testing)

ini_set('include_path', '/home/leon/gitlive/pear');

require_once 'File/DXF.php';

$f = new File_DXF();
$f->read("/home/leon/Dropbox/alan-leon/CA001.dxf");
//$f->read("/home/leon/Dropbox/alan-leon/KNT1431-SK-ST-001.dxf");
print_r($f);

*/

require_once 'File/DXF/BasicObject.php';

class File_DXF_Section extends File_DXF_BasicObject
{

    public $items = array();

    /*
     * OLD CODE BELOW
     */

    // protected $name;
    // protected $items = [];
    // protected $itemNames = [];

    /**
     * Section constructor.
     * @param $name
     * @param array $items
     */
    /*
    function __construct($name, $items = []) {
        $this->name = $name;
        $this->items = $items;
    }
    */

    /**
     * Adds an Item to the items list
     *
     * @param BasicObject $item
     */
    /*
    public function addItem(BasicObject $item) {
        $name = strtoupper($item->getName());
        if (!in_array($name, $this->itemNames, true)) {
        $this->itemNames[] = $name;
        $this->items[] = $item;
        } elseif ($this->name == 'tables') {
        foreach($this->items as $existing) {
            if (strtoupper($existing->getName()) == $name) {
            $entries = $item->getEntries();
            foreach($entries as $entry) {
                $existing->addEntry($entry);
            }
            }
        }
        }
    }
    */

    /**
     * Adds an array of Items to the items list
     *
     * @param array $items
     */
    /*
    public function addMultipleItems($items) {
        foreach ($items as $item) {
        $this->addItem($item);
        }
    }

    public function getItems() {
        return $this->items;
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
        array_push($output, 0, "SECTION");
        array_push($output, 2, strtoupper($this->name));
        foreach ($this->items as $item) {
        array_push($output, $item->render());
        }
        array_push($output, 0, "ENDSEC");
        return implode(PHP_EOL, $output);
    }
    */

    /**
     * Outputs an array representation of the Section
     * TODO: Find a good way to export the Section
     *
     * @return array
     */
    /*
    public function toArray() {
        return [];
    }
    */
    
}
