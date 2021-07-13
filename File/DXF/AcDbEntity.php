<?php

require_once 'File/DXF/Subclass.php';

class File_DXF_AcDbEntity extends File_DXF_Subclass
{
	public $isPaperSpace = 0; // 67
	public $layoutTabName; // 410
	public $layerName; // 8
	public $linetypeName = "BYLAYER"; // 6
	public $hardPointerToMaterial = "BYLATER"; // 347
	public $colorNumber = "BYLAYER"; // 62
	public $lineweightEnum; // 370
	public $linetypeScale = 1; // 48
	public $objectVisibility = 0; // 60
	public $proxyEntityGraphicsBytes; // 92
	public $proxyEntityGraphicsData; // 310
	public $colorValue; // 420
	public $colorName; // 430
	public $transparencyValue; // 440
	public $hardPointerToPlotStyle; // 309
	public $shadowMode; // 284

    function parse($dxf)
	{

		while($pair = $dxf->readPair()) {

            switch($pair['key']) {
                case 0:
				case 100:
				case 1001:
                    // End of this subclass
                    $dxf->pushPair($pair);
                    return;
				case 67:
					$this->isPaperSpace = $pair['value'];
					break;
				case 410:
					$this->layoutTabName = $pair['value'];
					break;
				case 8:
					$this->layerName = $pair['value'];
					break;
                case 6:
                    $this->linetypeName = $pair['value'];
                    break;
				case 347:
					$this->hardPointerToMarterial = $pair['value'];
					break;
                case 62:
                    $this->lineweightEnum = $pair['value'];
                    break;
                case 48:
                    $this->linetypeScale = $pair['value'];
                    break;
                case 60:
                    $this->objectVisibility = $pair['value'];
                    break;
                case 92:
                    $this->proxyEntityGraphicsBytes = $pair['value'];
                    break;
                case 310:
                    $this->proxyEntityGraphicsData = $pair['value'];
                    break;
                case 420:
                    $this->colorValue = $pair['value'];
                    break;
				case 430:
					$this->colorName = $pair['value'];
					break;
				case 440:
					$this->transparencyValue = $pair['value'];
					break;
				case 390:
					$this->hardPointerToPlotStyle = $pair['value'];
					break;
				case 284:
					$this->shadowMode = $pair['value'];
					break;
                default:
					$pairString = implode(", ", $pair); 
					throw new Exception ("Got unknown pair for subclass AcDbEntity ($pairString)");
					break;
            }
		}
	}
}