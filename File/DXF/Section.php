<?php

class File_DXF_Section extends File_DXF_BasicObject
{

    public $name;
    public $items = array();
    public $itemNames = array();

    
    function addItem(File_DXF_BasicObject $item)
    {
        $name = strtoupper($item->name);
        if (!in_array($name, $this->itemNames, true)) {
            $this->itemNames[] = $name;
            $this->items[] = $item;
        } elseif ($this->name == 'tables') {
            foreach ($this->items as $existing) {
                if (strtoupper($existing->name) == $name) {
                    $entries = $item->entries;
                    foreach ($entries as $entry) {
                        $existing->addEntry($entry);
                    }
                }
            }
        }
    }

    /**
     * Adds an array of Items to the items list
     *
     * @param array $items
     */
    function addMultipleItems($items)
    {
        foreach ($items as $item) {
            $this->addItem($item);
        }
    }

    /**
     * Public function to render an entity, returns a string representation of
     * the entity.
     * @return string
     */
    function render() {
        $output = array();
        array_push($output, 0, "SECTION");
        array_push($output, 2, strtoupper($this->name));
        foreach ($this->items as $item) {
            array_push($output, $item->render());
        }
        array_push($output, 0, "ENDSEC");
        return implode(PHP_EOL, $output);
    }

    /**
     * Outputs an array representation of the Section
     * TODO: Find a good way to export the Section
     *
     * @return array
     */
    function toArray() {
        return [];
    }
    
}
