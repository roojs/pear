<?php
/**
 * PHPWord
 *
 * Copyright (c) 2011 PHPWord
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   PHPWord
 * @package    PHPWord
 * @copyright  Copyright (c) 010 PHPWord
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt    LGPL
 * @version    Beta 0.6.3, 08.07.2011
 */


/**
 * PHPWord_Style_Font
 *
 * @category   PHPWord
 * @package    PHPWord_Style
 * @copyright  Copyright (c) 2011 PHPWord
 */
class Document_Word_Style_Font 
{
	
	const UNDERLINE_NONE		    = 'none';
	const UNDERLINE_DASH		    = 'dash';
	const UNDERLINE_DASHHEAVY		= 'dashHeavy';
	const UNDERLINE_DASHLONG		= 'dashLong';
	const UNDERLINE_DASHLONGHEAVY	= 'dashLongHeavy';
	const UNDERLINE_DOUBLE          = 'dbl';
	const UNDERLINE_DOTHASH		    = 'dotDash';
	const UNDERLINE_DOTHASHHEAVY	= 'dotDashHeavy';
	const UNDERLINE_DOTDOTDASH		= 'dotDotDash';
	const UNDERLINE_DOTDOTDASHHEAVY	= 'dotDotDashHeavy';
	const UNDERLINE_DOTTED		    = 'dotted';
	const UNDERLINE_DOTTEDHEAVY		= 'dottedHeavy';
	const UNDERLINE_HEAVY		    = 'heavy';
	const UNDERLINE_SINGLE		    = 'single';
	const UNDERLINE_WAVY		    = 'wavy';
	const UNDERLINE_WAVYDOUBLE		= 'wavyDbl';
	const UNDERLINE_WAVYHEAVY		= 'wavyHeavy';
	const UNDERLINE_WORDS		    = 'words';
	
	const FGCOLOR_YELLOW            = 'yellow';
	const FGCOLOR_LIGHTGREEN        = 'green';
	const FGCOLOR_CYAN              = 'cyan';
	const FGCOLOR_MAGENTA           = 'magenta';
	const FGCOLOR_BLUE              = 'blue';
	const FGCOLOR_RED               = 'red';
	const FGCOLOR_DARKBLUE          = 'darkBlue';
	const FGCOLOR_DARKCYAN          = 'darkCyan';
	const FGCOLOR_DARKGREEN         = 'darkGreen';
	const FGCOLOR_DARKMAGENTA       = 'darkMagenta';
	const FGCOLOR_DARKRED           = 'darkRed';
	const FGCOLOR_DARKYELLOW        = 'darkYellow';
	const FGCOLOR_DARKGRAY          = 'darkGray';
	const FGCOLOR_LIGHTGRAY         = 'lightGray';
	const FGCOLOR_BLACK             = 'black';
	
	/**
	 * Font style type
	 * 
	 * @var string
	 */
	private $type;
	
	/**
	 * Paragraph Style
	 * 
	 * @var PHPWord_Style_Paragraph
	 */
	private $paragraphStyle;
	
	private $size;
	private $name;
	private $bold;
	private $italic;
	private $superScript;
	private $subScript;
	private $underline;
	private $strikethrough;
	private $color;
	private $fgColor;

	/// not used - but set by setstyle	
	var $_align;
	var $_bgcolor;
	var $_fontStretch;
	var $_fontVariant;
	var $_href;
	var $_lang;
	var $_lineHeight;
	var $_marginBottom;
	var $_marginLeft;
	var $_marginRight;
	var $_marginTop;
	var $_Normal;
	var $_Reference;
	var $_textDecoration;
	var $_textIndent;
	var $_textPosition;
	var $_textTransform;
	var $_widows;
	
	
	
	public function __construct($type = 'text', $styleParagraph = null) 
        {
		$this->type            = $type;
		$this->name            = 'Arial';
		$this->size            = 20;
		$this->bold		    = false;
		$this->italic		    = false;
		$this->superScript	    = false;
		$this->subScript	    = false;
		$this->underline	    = Document_Word_Style_Font::UNDERLINE_NONE;
		$this->strikethrough   = false;
		$this->color           = '000000';
		$this->fgColor         = null;
		
		if(!is_null($styleParagraph)) {
                        require_once __DIR__ . '/Paragraph.php';
			$paragraph = new Document_Word_Style_Paragraph();
			foreach($styleParagraph as $key => $value) {
				if(substr($key, 0, 1) != '_') {
					$key = '_'.$key;
				}
				$paragraph->setStyleValue($key, $value);
			}
			$this->paragraphStyle = $paragraph;
		} else {
			$this->paragraphStyle = null;
		}
	}

	public function getName() 
        {
		return $this->name;
	}
	
	public function setStyleValue($key, $value) 
     {
		if($key == '_size') {
			$value = (int) $value;
			$value *= 2;
		}
		
		$cache = array();
		if (empty($cache)) {
			$ar = get_class_vars(get_class($this));
			foreach($ar as $k => $v) {
				$cache[strtolower($k)] = $k;
			}
		}
		$key = strtolower(str_replace('-','', $key));
		$key = isset($cache[$key]) ? $cache[$key] : $key;
		
		$this->$key =  $value;
	}
	
	public function setName($pValue = 'Arial') 
        {
		if($pValue == '') {
			$pValue = 'Arial';
		}
		$this->name = $pValue;
		return $this;
	}

	public function getSize() 
        {
		return $this->size;
	}

	public function setSize($pValue = 20) 
        {
		if($pValue == '') {
			$pValue = 20;
		}
		$this->size = ($pValue*2);
		return $this;
	}

	public function getBold() 
        {
		return $this->bold;
	}

	public function setBold($pValue = false) 
        {
		if($pValue == '') {
			$pValue = false;
		}
		$this->bold = $pValue;
		return $this;
	}

	public function getItalic() 
        {
		return $this->italic;
	}

	public function setItalic($pValue = false) 
        {
		if($pValue == '') {
			$pValue = false;
		}
		$this->italic = $pValue;
		return $this;
	}

	public function getSuperScript() 
        {
		return $this->superScript;
	}

	public function setSuperScript($pValue = false) 
        {
		if($pValue == '') {
			$pValue = false;
		}
		$this->superScript = $pValue;
		$this->subScript = !$pValue;
		return $this;
	}

	public function getSubScript() 
        {
		return $this->subScript;
	}

	public function setSubScript($pValue = false) 
        {
		if($pValue == '') {
			$pValue = false;
		}
		$this->subScript = $pValue;
		$this->superScript = !$pValue;
		return $this;
	}

	public function getUnderline() 
        {
		return $this->underline;
	}

	public function setUnderline($pValue = Document_Word_Style_Font::UNDERLINE_NONE) 
        {
		if ($pValue == '') {
			$pValue = Document_Word_Style_Font::UNDERLINE_NONE;
		}
		$this->underline = $pValue;
		return $this;
	}

	public function getStrikethrough() 
        {
		return $this->strikethrough;
	}

	public function setStrikethrough($pValue = false) 
        {
		if($pValue == '') {
			$pValue = false;
		}
		$this->strikethrough = $pValue;
		return $this;
	}
	
	public function getColor() 
        {
		return $this->color;
	}

	public function setColor($pValue = '000000') 
        {
	   $this->color = $pValue;
	   return $this;
	}

	public function getFgColor() 
        {
		return $this->fgColor;
	}

	public function setFgColor($pValue = null) 
        {
	   $this->fgColor = $pValue;
	   return $this;
	}
	
	public function getStyleType() 
        {
		return $this->type;
	}
	
	/**
	 * Get Paragraph style
	 * 
	 * @return PHPWord_Style_Paragraph
	 */
	public function getParagraphStyle() 
        {
		return $this->paragraphStyle;
	}
}
?>
