<?php

require_once 'File/DXF/Section.php';

class File_DXF_SectionBlocks extends File_DXF_Section
{

	public $name = 'blocks';
	 
    function parse($dxf) {
		
		while ($pair = $dxf->readPair()) {

			if($pair['key'] == 0) {

				if ($pair['value'] == 'ENDSEC') {
					// End of the blocks section
					return;
				}

				if ($pair['value'] == 'BLOCK') {
					// Beginning of a new block
					$entity = $dxf->factory('Block');
					$entity->parse($dxf);
					$this->items[] = $entity;
					continue;798
				}

				$pairString = implode(", ", $pair);
				throw new Exception ("Got invalid pair for a block definition ($pairString)");
			}
		}
	}

}
