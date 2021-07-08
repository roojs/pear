<?php

require_once 'File/DXF/Section.php';

class File_DXF_SectionEntities extends File_DXF_Section
{

    public $name = 'entities';
	 
    public function parse($dxf, $opts= array())
    {
        $entityType = '';
		$data = [];
		$types = array(
			'3DFACE', '3DSOLID', 'ACAD_PROXY_ENTITY', 'ARC', 'ATTDEF', 'ATTRIB', 'BODY',
			'CIRCLE', 'DIMENSION', 'ELLIPSE', 'HATCH', 'HELIX', 'IMAGE', 'INSERT', 'LEADER',
			'LIGHT', 'LINE', 'LWPOLYLINE', 'MESH', 'MLINE', 'MLEADERSTYLE', 'MLEADER',
			'MTEXT', 'OLEFRAME', 'OLE2FRAME', 'POINT', 'POLYLINE', 'RAY', 'REGION', 'SECTION', 
			'SEQEND', 'SHAPE', 'SOLID', 'SPLINE', 'SUN', 'SURFACE', 'TABLE', 'TEXT', 
			'TOLERANCE', 'TRACE', 'UNDERLAY', 'VERTEX', 'VIEWPOINT', 'WIPEOUT', 'XLINE',
		);
	
		while ($pair = $dxf->readPair()) {

			if($pair['key'] == 0) {
				if (!empty($data)) {
					switch($entityType) {
						case '3DFACE': 
						case '3DSOLID': 
						case 'ACAD_PROXY_ENTITY': 
						case 'ARC': 
						case 'ATTDEF': 
						case 'ATTRIB': 
						case 'BODY':
						case 'CIRCLE': 
						case 'DIMENSION': 
						case 'ELLIPSE': 
						case 'HATCH': 
						case 'HELIX': 
						case 'IMAGE': 
						case 'INSERT': 
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
						case 'SEQEND': 
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
					}
				}
				if ($pair['key'] == 'ENDSEC') {
					// End of the entities section
					break;
				} else {
					// Beginning of a new entity
					$entitiyType = $pair['value'];
					$data = [];
				}
			}

			if (in_array($entityType, $types)) {
				$data[$pair['key']] =$pair['value'];
			}
		}
    }

}
