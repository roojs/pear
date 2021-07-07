<?php

require_once 'File/DXF/Section.php';

class File_DXF_SectionEntities extends File_DXF_Section
{
    public function __construct()
    {
        parent::__construct('entities');
    }
    public function parse($dxf, $opts= array())
    {
        $entities = [];
        $entityType = '';
	$data = [];
	$types = array(
	    '3DFACE', '3DSOLID', 'ACAD_PROXY_ENTITY', 'ARC', 'ATTDEF', 'ATTRIB', 'BODY', 'CIRCLE', 'DIMENSION',
	    'ELLIPSE', 'HATCH', 'HELIX', 'IMAGE', 'INSERT', 'LEADER', 'LIGHT', 'LINE', 'LWPOLYLINE', 'MESH', 
	    'MLINE', 'MLEADERSTYLE', 'MLEADER', 'MTEXT', 'OLEFRAME', 'OLE2FRAME', 'POINT', 'POLYLINE', 'RAY', 
	    'REGION', 'SECTION', 'SEQEND', 'SHAPE', 'SOLID', 'SPLINE', 'SUN', 'SURFACE', 'TABLE', 'TEXT', 
	    'TOLERANCE', 'TRACE', 'UNDERLAY', 'VERTEX', 'VIEWPOINT', 'WIPEOUT', 'XLINE',
	);
	// TODO most entity types are still missing
	
	while ($pair = $dxf->readPair()) {
	
	    if ($pair['value'] == 'ENDSEC') {
	        if (in_array($entityType, $types) && !empty($data)) {
	            $entity = $this->addReadEntity($entityType, $data, $opts['move'], $opts['rotate']);
                    if ($entity) {
                        $entities[] = $entity;
                        this.addItem($entity);
                    }
	        }
                break;
            }
            
            if ($pair['key'] == 0) {
                if ((in_array($entityType, $types) && !empty($data)) || in_array($entityType, ['POLYLINE', 'VERTEX']) && $pair['value'] == 'SEQEND') {
                    $entity = $this->addReadEntity($entityType, $data, $opts['move'], $opts['rotate']);
                    if ($entity) {
                        $entities[] = $entity;
                        this.addItem($entity);
                    }
                    $data = [];
		}
		$entityType = $pair['value'];
		if ($pair['value'] == 'VERTEX') {
		    $data['points'][] = [];
		}
		if ($pair['value'] == 'SPLINE') {
		    $data['knots'] = [];
		    $data['points'] = [];
		}
		if ($pair['value'] == 'LWPOLYLINE') {
		    $data['points'] = [];
		}
            } else {
                if ($entityType == 'SPLINE' && in_array($pair['key'], [10, 20, 30, 40])) {
                    switch ($pair['key']) {
                        case 10:
                            $data['points'][] = [10 => $pair['value'], 20 => 0, 30 => 0];
                            break;
                        case 20:
                        case 30:
                            $data['points'][sizeof($data['points']) - 1][$pair['key']] = $pair['value'];
                            break;
			case 40:
			    $data['knots'][] = $pair['value'];
			    break;
		    }
		} elseif ($entityType == 'LWPOLYLINE' && in_array($pair['key'], [10, 20, 42])) {
		    switch ($pair['key']) {
		        case 10:
		            $data['points'][] = [10 => $pair['value'], 20 => 0, 42 => 0];
		            break;
			case 20:
			case 42:
			    $data['points'][sizeof($data['points']) - 1][$pair['key']] = $pair['value'];
		            break;
		    }
		} elseif ($entityType == 'VERTEX') {
		    $data['points'][count($data['points']) - 1][$pair['key']] = $pair['value'];
		} elseif (in_array($entityType, $types) || $entityType == 'POLYLINE') {
		    $data[$pair['key']] = $pair['value'];
		}
	    }
	}
	
	return $entities;
    }


    static public function addReadEntity($type, $data, $move = [0, 0, 0], $rotate = 0)
	{


		switch ($type) {
			case 'TEXT':
				$point = [$data[10], $data[20], $data[30]];
				$rotation = $data[50] ? $data[50] : 0;
				$thickness = $data[39] ? $data[39] : 0;
				require_once 'File/DXF/Text.php';

				$text = new File_DXF_Text($data[1], $point, $data[40], $rotation, $thickness);
				if ($data[72]) {
					$text->setHorizontalJustification($data[72]);
				}
				if ($data[73]) {
					$text->setVerticalJustification($data[73]);
				}
				$text->move($move);
				$text->rotate($rotate);
				return $text;
			case 'LINE':
				$start = [$data[10], $data[20], $data[30]];
				$end = [$data[11], $data[21], $data[31]];
				$thickness = $data[39] ? $data[39] : 0;
				$extrusion = [
					$data[210] ? $data[210] : 0,
					$data[220] ? $data[220] : 0,
					$data[230] ? $data[230] : 1
				];
				require_once 'File/DXF/Line.php';

				$line = new File_DXF_Line($start, $end, $thickness, $extrusion);
				if (isset($data[62])) {
					$line->setColor($data[62]);
				}
				$line->move($move);
				$line->rotate($rotate);
				return $line;
			case 'ELLIPSE':
				$center = [$data[10], $data[20], $data[30]];
				$endpoint = [$data[11], $data[21], $data[31]];
				$start = $data[41] ? $data[41] : 0;
				$end = $data[42] ? $data[42] : M_PI * 2;
				$extrusion = [
					$data[210] ? $data[210] : 0,
					$data[220] ? $data[220] : 0,
					$data[230] ? $data[230] : 1
				];
				require_once 'File/DXF/Ellipse.php';

				$ellipse = new File_DXF_Ellipse($center, $endpoint, $data[40], $start, $end, $extrusion);
				if (isset($data[62])) {
					$ellipse->setColor($data[62]);
				}
				$ellipse->move($move);
				$ellipse->rotate($rotate);
				return $ellipse;
			case 'SPLINE':
				$base = [0, 0, 0];
				if (isset($data[210])) {
					$base = [$data[210], $data[220], $data[230]];
				}
				$start = [0, 0, 0];
				if (isset($data[12])) {
					$start = [$data[12], $data[22], $data[32]];
				}
				$end = [0, 0, 0];
				if (isset($data[13])) {
					$end = [$data[13], $data[23], $data[33]];
				}
				require_once 'File/DXF/Spline.php';

				$spline = new File_DXF_Spline(isset($data[71]) ? $data[71] : 1, $base, $start, $end);
				if (isset($data[62])) {
					$spline->setColor($data[62]);
				}
				if (isset($data[70])) {
					$bin = decbin($data[70]);
					$length = strlen((string)$bin);
					for ($i = $length - 1; $i >= 0; $i--) {
						if (boolval($bin[$i])) {
							$spline->setFlag($length - 1 - $i, $bin[$i]);
						}
					}
				}
				foreach ($data['knots'] as $knot) {
					$spline->addKnot($knot);
				}
				foreach ($data['points'] as $point) {
					$spline->addPoint([$point[10], $point[20], $point[30]]);
				}
				return $spline;
			case 'INSERT':
				$point = [$data[10], $data[20], $data[30]];
				$scale = [
					isset($data[41]) ? $data[41] : 1,
					isset($data[42]) ? $data[42] : 1,
					isset($data[43]) ? $data[43] : 1,
				];
				$rotation = isset($data[50]) ? $data[50] : 0;
				require_once 'File/DXF/Insert.php';

				$insert = new File_DXF_Insert($data[2], $point, $scale, $rotation);
				$insert->move($move);
				return $insert;
			case 'LWPOLYLINE':
			case 'POLYLINE':
			case 'VERTEX':
				require_once 'File/DXF/LWPolyline.php';
				require_once 'File/DXF/Polyline.php';

				if (isset($data[100])) {
					switch ($data[100]) {
						case 'AcDbPolyline':
							$polyline = new File_DXF_LWPolyline();
							break;
						case 'AcDb2dPolyline':

							$polyline = new File_DXF_Polyline(2);
							break;
						case 'AcDb3dPolyline':
							$polyline = new File_DXF_Polyline(3);
							break;
						default:
							echo 'The polyline type ' . $data[100] . ' has not been found' . PHP_EOL;
							return false;
					}
				} else {
					$polyline = new File_DXF_Polyline(2);
				}
				if (isset($data[62])) {
					$polyline->setColor($data[62]);
				}
				if (isset($data[70])) {
					$bin = decbin($data[70]);
					$length = strlen((string)$bin);
					for ($i = $length - 1; $i >= 0; $i--) {
						if (boolval($bin[$i])) {
							$polyline->setFlag($length - 1 - $i, $bin[$i]);
						}
					}
				}
				foreach ($data['points'] as $point) {
					$bulge = isset($point[42]) ? $point[42] : 0;
					$polyline->addPoint([$point[10], $point[20], $point[30]], $bulge);
				}
				$polyline->move($move);
				$polyline->rotate($rotate);
				return $polyline;
			case 'CIRCLE':
				$center = [$data[10], $data[20], $data[30]];
				$thickness = $data[39] ? $data[39] : 0;
				$extrusion = [
					$data[210] ? $data[210] : 0,
					$data[220] ? $data[220] : 0,
					$data[230] ? $data[230] : 1
				];
				require_once 'File/DXF/Circle.php';

				$circle = new File_DXF_Circle($center, $data[40], $thickness, $extrusion);

				$circle->move($move);

				return $circle;
			case 'ARC':
				$center = [$data[10], $data[20], $data[30]];
				$thickness = $data[39] ? $data[39] : 0;
				$extrusion = [
					$data[210] ? $data[210] : 0,
					$data[220] ? $data[220] : 0,
					$data[230] ? $data[230] : 1
				];
				require_once 'File/DXF/Arc.php';

				$arc = new File_DXF_Arc(
					$center,
					$data[40],
					$data[50],
					$data[51],
					$thickness,
					$extrusion
				);

				$arc->move($move);

				return $arc;
		}
		return false;
	}

	public function addMultipleEntities($entities)
	{
		foreach ($entities as $entity) {
			$this->entities->addItem($entity);
		}
	}
}
