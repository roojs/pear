<?php

require_once 'File/DXF/Subclass.php';

class File_DXF_AcDbBlockBegin extends File_DXF_Subclass
{

	function parseToEntity($dxf, $entity)
	{

		while($pair = $dxf->readPair()) {

			switch($pair['key']) {
				case 0:
				case 100:
				case 1001:
					// End of a subclass
					$dxf->pushPair($pair);
					return;
				case 2:
				case 3:
					$this->blockName = $pair['value'];
					break;
				case 70:
					$this->blockTypeFlags = $pair['value'];
					break;
				case 10:
					$this->basePointX = $pair['value'];
					break;
				case 20:
					$this->basePointY = $pair['value'];
					break;
				case 30:
					$this->basePointZ = $pair['value'];
					break;
				case 1:
					$this->xRefPathName = $pair['value'];
					break;
				case 4:
					$this->blockDescription = $pair['value'];
					break;
				default:
					$pairString = implode(", ", $pair); 
					throw new Exception ("Got unknown pair for subclass AcDbBlockBegin ($pairString)");
					break;
			}
		}
	}
}