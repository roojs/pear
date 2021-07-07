<?php

require_once 'File/DXF/Section.php';
require_once 'File/DXF/SectionEntities.php';

class File_DXF_SectionBlocks extends File_DXF_Section
{
    public function __construct()
    {
        parent::__construct('blocks');
    }
    public function parse($handle)
    {
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

	public function readEntitiesSection($values){
		$entities = [];
		$entityType = '';
		$data = [];
		$types = ['TEXT', 'LINE', 'ELLIPSE', 'SPLINE', 'INSERT', 'ARC', 'CIRCLE', 'LWPOLYLINE'];
		// TODO most entity types are still missing
		foreach ($values as $value) {
		  if ($value['key'] == 0) {
			// This condition happens at the end of an entity
			if (
			  (in_array($entityType, $types) && !empty($data)) ||
			  in_array($entityType, ['POLYLINE', 'VERTEX']) && $value['value'] == 'SEQEND'
			) {
			  $entity = File_DXF_SectionEntities::addReadEntity($entityType, $data);
			  if ($entity) {
				$entities[] = $entity;
			  }
			  $data = [];
			}
			$entityType = $value['value'];
			if ($value['value'] == 'VERTEX') {
			  $data['points'][] = [];
			}
			if ($value['value'] == 'SPLINE') {
			  $data['knots'] = [];
			  $data['points'] = [];
			}
			if ($value['value'] == 'LWPOLYLINE') {
			  $data['points'] = [];
			}
		  } else {
			if ($entityType == 'SPLINE' && in_array($value['key'], [10, 20, 30, 40])) {
			  switch ($value['key']) {
				case 10:
				  $data['points'][] = [10 => $value['value'], 20 => 0, 30 => 0];
				  break;
				case 20:
				case 30:
				  $data['points'][sizeof($data['points']) -1 ][$value['key']] = $value['value'];
				  break;
				case 40:
				  $data['knots'][] = $value['value'];
				  break;
			  }
			} elseif ($entityType == 'LWPOLYLINE' && in_array($value['key'], [10, 20, 42])) {
			  switch ($value['key']) {
				case 10:
				  $data['points'][] = [10 => $value['value'], 20 => 0, 42 => 0];
				  break;
				case 20:
				case 42:
				  $data['points'][sizeof($data['points']) -1 ][$value['key']] = $value['value'];
				  break;
			  }
			} elseif (in_array($entityType, $types) || $entityType == 'POLYLINE') {
			  $data[$value['key']] = $value['value'];
			} elseif ($entityType == 'VERTEX') {
			  $data['points'][count($data['points']) - 1][$value['key']] = $value['value'];
			}
		  }
		}
		if (in_array($entityType, $types) && !empty($data)) {
		  $entity = File_DXF_SectionEntities::addReadEntity($entityType, $data);
		  if ($entity) {
			$entities[] = $entity;
		  }
		}
	
		return $entities;
	}
}
