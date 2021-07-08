<?php

require_once 'File/DXF/Section.php';

class File_DXF_SectionEntities extends File_DXF_Section
{

    public $name = 'entities';
	 
    public function parse($dxf, $opts= array())
    {
		while ($pair = $dxf->readPair()) {

			if($pair['key'] == 0) {
				if ($pair['key'] == 'ENDSEC') {
					// End of the entities section
					break;
				} else {
					// Beginning of a new entity
					$entityType = $pair['value'];
					switch($entityType) {
						case 'INSERT':
							$entity = $dxf->factory('Insert');
							$entity->phase($dxf);
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
						case 'VIEWPOINT': 
						case 'WIPEOUT': 
						case'XLINE':
							// These entity are skipped in the current phase
							break;
					}
				}
			}
		}
    }

}