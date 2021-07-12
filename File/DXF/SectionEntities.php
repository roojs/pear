<?php

require_once 'File/DXF/Section.php';

class File_DXF_SectionEntities extends File_DXF_Section
{

    public $name = "entities";
	 
    public function parse($dxf, $opts= array())
    {

		while ($pair = $dxf->readPair()) {

			if($pair['key'] == 0) {

				if ($pair['value'] == 'ENDSEC') {
					// End of the entities section
					break;
				}

				// Beginning of a new entity
				switch($pair['value']) {
					case 'INSERT':
						echo "ZZZ\n";
						$entity = $dxf->factory('Insert');
						$entity->parse($dxf);
						$this->items[] = $entity;
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
						break;
					default:
						$entityType = $pair['value'];
						throw new Exception ("Got unknown entity type ($entityType)");
						break;
				}
			}
		}
    }

}
