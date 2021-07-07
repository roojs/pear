<?php

class File_DXF_Section extends File_DXF_BasicObject
{

    public $name;
    public $items = array();
    public $itemNames = array();

    
    public function addItem(File_DXF_BasicObject $item)
    {
        $name = strtoupper($item->getName());
        if (!in_array($name, $this->itemNames, true)) {
            $this->itemNames[] = $name;
            $this->items[] = $item;
        } elseif ($this->name == 'tables') {
            foreach ($this->items as $existing) {
                if (strtoupper($existing->getName()) == $name) {
                    $entries = $item->getEntries();
                    foreach ($entries as $entry) {
                        $existing->addEntry($entry);
                    }
                }
            }
        }
    }
    
    /**
	 *
	 * TODO ENHANCE / CHECK THE CODE BLOEW
	 *
	 */

    /**
     * Adds an array of Items to the items list
     *
     * @param array $items
     */
    public function addMultipleItems($items)
    {
        foreach ($items as $item) {
            $this->addItem($item);
        }
    }

    public function getItems()
    {
        return $this->items;
    }
    
}
