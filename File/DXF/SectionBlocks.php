<?php

require_once 'File/DXF/Section.php';
require_once 'File/DXF/SectionEntities.php';

class File_DXF_SectionBlocks extends File_DXF_Section
{
	 
    public function parse($handle)
    {

		public $name = 'blocks';
		
        $block = [];
		$entitiesSection = [];
		require_once 'File/DXF/Block.php';

		while ($pair = $this->readPair($handle)) {
            if ($pair['value'] == 'ENDSEC') {
                break;
            }
			if ($pair['key'] == 0) {
				switch ($pair['value']) {
					case 'BLOCK':
						$block = [];
						break;
					case 'ENDBLK':
						$blockEntity = new File_DXF_Block($block[2]);
						$entities = $this->readEntitiesSection($entitiesSection);
						foreach ($entities as $entity) {
							$blockEntity->addEntity($entity);
						}
						$this->addItem($blockEntity);
						break;
					default:
						$entitiesSection[] = $pair;
				}
			} elseif (empty($entitiesSection)) {
				$block[$pair['key']] = $pair['value'];
			} else {
				$entitiesSection[] = $pair;
			}
		}
    }
}
