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

require_once __DIR__.'/WriterPart.php';
class Document_Word_Writer_Writer_Word2007_Base extends Document_Word_Writer_Writer_Word2007_WriterPart 
{
	
	protected function _writeText(Document_Word_Writer_Shared_XMLWriter $objWriter = null, Document_Word_Writer_Section_Text $text, $withoutP = false) 
        {
                
		$styleFont = $text->getFontStyle();
		
		$SfIsObject = ($styleFont instanceof Document_Word_Writer_Style_Font) ? true : false;
		
		if(!$withoutP) {
			$objWriter->startElement('w:p');
			
			$styleParagraph = $text->getParagraphStyle();
			$SpIsObject = ($styleParagraph instanceof Document_Word_Writer_Style_Paragraph) ? true : false;
			
			if($SpIsObject) {
				$this->_writeParagraphStyle($objWriter, $styleParagraph);
			} elseif(!$SpIsObject && !is_null($styleParagraph)) {
				$objWriter->startElement('w:pPr');
					$objWriter->startElement('w:pStyle');
						$objWriter->writeAttribute('w:val', $styleParagraph);
					$objWriter->endElement();
				$objWriter->endElement();
			}
		}
		require_once __DIR__ . '/../../Shared/String.php';
		$strText = htmlspecialchars($text->getText()); // technically write->text() does this..
		$strText = Document_Word_Writer_Shared_String::ControlCharacterPHP2OOXML($strText);
		$strText = str_replace('&amp;', '&', $strText); // htmlspecialchars going overboard..
		$objWriter->startElement('w:r');
			if($SfIsObject) {
				$this->_writeTextStyle($objWriter, $styleFont);
			} elseif(!$SfIsObject && !is_null($styleFont)) {
				$objWriter->startElement('w:rPr');
					$objWriter->startElement('w:rStyle');
						$objWriter->writeAttribute('w:val', $styleFont);
					$objWriter->endElement();
				$objWriter->endElement();
			}
		
			$objWriter->startElement('w:t');
				$objWriter->writeAttribute('xml:space', 'preserve'); // needed because of drawing spaces before and after text
                 
                $objWriter->text($strText);
				//$objWriter->text($strText);
			$objWriter->endElement();
			
		$objWriter->endElement(); // w:r
		
		if(!$withoutP) {
			$objWriter->endElement(); // w:p
		}
	}
	
	protected function _writeTextRun(Document_Word_Writer_Shared_XMLWriter $objWriter = null, Document_Word_Writer_Section_TextRun $textrun) 
        {
		
              
               $elements = $textrun->getElements();
		$styleParagraph = $textrun->getParagraphStyle();
		
		$SpIsObject = ($styleParagraph instanceof Document_Word_Writer_Style_Paragraph) ? true : false;
		
		$objWriter->startElement('w:p');
		
		if($SpIsObject) {
			$this->_writeParagraphStyle($objWriter, $styleParagraph);
		} elseif(!$SpIsObject && !is_null($styleParagraph)) {
			$objWriter->startElement('w:pPr');
				$objWriter->startElement('w:pStyle');
					$objWriter->writeAttribute('w:val', $styleParagraph);
				$objWriter->endElement();
			$objWriter->endElement();
		}
		
		if(count($elements) > 0) {
			foreach($elements as $element) {
                                //echo get_class($element) .'<br/>';
				if($element instanceof Document_Word_Writer_Section_Text) {
					$this->_writeText($objWriter, $element, true);
				} elseif($element instanceof Document_Word_Writer_Section_Link) {
					$this->_writeLink($objWriter, $element, true);
				} elseif($element instanceof Document_Word_Writer_Section_Image ||
                                        $element instanceof Document_Word_Writer_Section_MemoryImage) {
                                        $this->_writeImage($objWriter, $element, true); // skip the image para
                                } elseif($element instanceof Document_Word_Writer_Section_TextBreak) {
                                        $this->_writeTextBreak($objWriter);
                                } elseif($element instanceof Document_Word_Writer_Section_Footer_PreserveText) {
                                        $this->_writePreserveText($objWriter, $element,true);
                                } elseif($element instanceof Document_Word_Writer_Section_PageBreak) {
                                        $this->_writePageBreak($objWriter , true);
                                } else {
                                    throw Exception("unhandled class" . get_class($element));
                                }
			}
		}
		
		$objWriter->endElement();
	}
        
        private function _writePageBreak(Document_Word_Writer_Shared_XMLWriter $objWriter = null , $skip_para = false) 
        {
		if(!$skip_para){
                    $objWriter->startElement('w:p');
                }
			$objWriter->startElement('w:r');
				$objWriter->startElement('w:br');
					$objWriter->writeAttribute('w:type', 'page');
				$objWriter->endElement();
			$objWriter->endElement();
                if(!$skip_para){
                    $objWriter->endElement();
                }
	}
	
	protected function _writeParagraphStyle(Document_Word_Writer_Shared_XMLWriter $objWriter = null, Document_Word_Writer_Style_Paragraph $style, $withoutPPR = false) 
        {
		$align = $style->getAlign();
                // microsoft office default line spacing is 10pt, we need to set it to 0 if we have not set the spacing..
		$spaceBefore = is_null($style->getSpaceBefore()) ? 0 : $style->getSpaceBefore();
		$spaceAfter = is_null($style->getSpaceAfter()) ? 0 : $style->getSpaceAfter();
		$spacing = is_nan($style->getSpacing()) ? 0 : $style->getSpacing();
		
        
		if(!is_null($align) || !is_null($spacing) || !is_null($spaceBefore) || !is_null($spaceAfter)) {
			
            if(!$withoutPPR) {
                $objWriter->startElement('w:pPr');
            }
			
			if(!is_null($align)) {
				$objWriter->startElement('w:jc');
					$objWriter->writeAttribute('w:val', $align);
				$objWriter->endElement();
			}
			
			if(!is_null($spaceBefore) || !is_null($spaceAfter) || !is_null($spacing)) {
				
				$objWriter->startElement('w:spacing');
				
					if(!is_null($spaceBefore)) {
						$objWriter->writeAttribute('w:before', $spaceBefore);
					}
					if(!is_null($spaceAfter)) {
						$objWriter->writeAttribute('w:after', $spaceAfter);
					}
					if(!is_null($spacing)) {
						$objWriter->writeAttribute('w:line', $spacing);
						$objWriter->writeAttribute('w:lineRule', 'auto');
					}

				$objWriter->endElement();
			}
			
            if(!$withoutPPR) {
			    $objWriter->endElement(); // w:pPr
            }
		}
	}
	
	protected function _writeLink(Document_Word_Writer_Shared_XMLWriter $objWriter = null, Document_Word_Writer_Section_Link $link, $withoutP = false) 
        {
		$rID = $link->getRelationId();
		$linkName = $link->getLinkName();
		if(is_null($linkName)) {
			$linkName = $link->getLinkSrc();
		}
		
		$styleFont = $link->getFontStyle();
		$SfIsObject = ($styleFont instanceof Document_Word_Writer_Style_Font) ? true : false;
		
		if(!$withoutP) {
			$objWriter->startElement('w:p');
			
			$styleParagraph = $link->getParagraphStyle();
			$SpIsObject = ($styleParagraph instanceof Document_Word_Writer_Style_Paragraph) ? true : false;
			
			if($SpIsObject) {
				$this->_writeParagraphStyle($objWriter, $styleParagraph);
			} elseif(!$SpIsObject && !is_null($styleParagraph)) {
				$objWriter->startElement('w:pPr');
					$objWriter->startElement('w:pStyle');
						$objWriter->writeAttribute('w:val', $styleParagraph);
					$objWriter->endElement();
				$objWriter->endElement();
			}
		}
		
			$objWriter->startElement('w:hyperlink');
				$objWriter->writeAttribute('r:id', 'rId'.$rID);
				$objWriter->writeAttribute('w:history', '1');
				
				$objWriter->startElement('w:r');
					if($SfIsObject) {
						$this->_writeTextStyle($objWriter, $styleFont);
					} elseif(!$SfIsObject && !is_null($styleFont)) {
						$objWriter->startElement('w:rPr');
							$objWriter->startElement('w:rStyle');
								$objWriter->writeAttribute('w:val', $styleFont);
							$objWriter->endElement();
						$objWriter->endElement();
					}
				
					$objWriter->startElement('w:t');
						$objWriter->writeAttribute('xml:space', 'preserve'); // needed because of drawing spaces before and after text
						$objWriter->text($linkName);
					$objWriter->endElement();
				$objWriter->endElement();
		
			$objWriter->endElement();
		
		if(!$withoutP) {
			$objWriter->endElement(); // w:p
		}
	}
	
	protected function _writePreserveText(Document_Word_Writer_Shared_XMLWriter $objWriter = null, Document_Word_Writer_Section_Footer_PreserveText $textrun, $skip_para = false) 
        {
		$styleFont = $textrun->getFontStyle();
		$styleParagraph = $textrun->getParagraphStyle();
		
		$SfIsObject = ($styleFont instanceof Document_Word_Writer_Style_Font) ? true : false;
		$SpIsObject = ($styleParagraph instanceof Document_Word_Writer_Style_Paragraph) ? true : false;
		
                
		$arrText = $textrun->getText();
                
                    $objWriter->startElement('w:p');
			if($SpIsObject) {
				$this->_writeParagraphStyle($objWriter, $styleParagraph);
			} elseif(!$SpIsObject && !is_null($styleParagraph)) {
				$objWriter->startElement('w:pPr');
					$objWriter->startElement('w:pStyle');
						$objWriter->writeAttribute('w:val', $styleParagraph);
					$objWriter->endElement();
				$objWriter->endElement();
			}
			foreach($arrText as $text) {
                            if(substr($text, 0, 1) == '{') {
					$text = substr($text, 1, -1);
					
					$objWriter->startElement('w:r');
						$objWriter->startElement('w:fldChar');
							$objWriter->writeAttribute('w:fldCharType', 'begin');
						$objWriter->endElement();
					$objWriter->endElement();
					
					$objWriter->startElement('w:r');
					
						if($SfIsObject) {
							$this->_writeTextStyle($objWriter, $styleFont);
						} elseif(!$SfIsObject && !is_null($styleFont)) {
							$objWriter->startElement('w:rPr');
								$objWriter->startElement('w:rStyle');
									$objWriter->writeAttribute('w:val', $styleFont);
								$objWriter->endElement();
							$objWriter->endElement();
						}
						
						$objWriter->startElement('w:instrText');
							$objWriter->writeAttribute('xml:space', 'preserve');
							$objWriter->writeRaw($text);
						$objWriter->endElement();
					$objWriter->endElement();
					
					$objWriter->startElement('w:r');
						$objWriter->startElement('w:fldChar');
							$objWriter->writeAttribute('w:fldCharType', 'separate');
						$objWriter->endElement();
					$objWriter->endElement();
					$objWriter->startElement('w:r');
						$objWriter->startElement('w:fldChar');
							$objWriter->writeAttribute('w:fldCharType', 'end');
						$objWriter->endElement();
					$objWriter->endElement();
				} else {
					$text = htmlspecialchars($text);
                    require_once 'Document/Word/Writer/Shared/String.php';
					$text = Document_Word_Writer_Shared_String::ControlCharacterPHP2OOXML($text);
					
					$objWriter->startElement('w:r');
						
						if($SfIsObject) {
							$this->_writeTextStyle($objWriter, $styleFont);
						} elseif(!$SfIsObject && !is_null($styleFont)) {
							$objWriter->startElement('w:rPr');
								$objWriter->startElement('w:rStyle');
									$objWriter->writeAttribute('w:val', $styleFont);
								$objWriter->endElement();
							$objWriter->endElement();
						}
						
						$objWriter->startElement('w:t');
							$objWriter->writeAttribute('xml:space', 'preserve');
							$objWriter->text($text);
						$objWriter->endElement();
					$objWriter->endElement();
				}
			}
		
		
                    $objWriter->endElement(); // p
               
	}
	
	protected function _writeTextStyle(Document_Word_Writer_Shared_XMLWriter $objWriter = null, Document_Word_Writer_Style_Font $style) 
        {
		$font = $style->getName();
		$bold = $style->getBold();
		$italic = $style->getItalic();
		$color = $style->getColor();
		$size = $style->getSize();
		$fgColor = $style->getFgColor();
		$striketrough = $style->getStrikethrough();
		$underline = $style->getUnderline();
		
		$objWriter->startElement('w:rPr');
		
		// Font
		if($font != 'Arial') {
			$objWriter->startElement('w:rFonts');
				$objWriter->writeAttribute('w:ascii', $font);
				$objWriter->writeAttribute('w:hAnsi', $font);
				$objWriter->writeAttribute('w:cs', $font);
			$objWriter->endElement();
		}
		
		// Color
		if($color != '000000') {
			$objWriter->startElement('w:color');
				$objWriter->writeAttribute('w:val', $color);
			$objWriter->endElement();
		}
		
		// Size
		if($size != 20) {
			$objWriter->startElement('w:sz');
				$objWriter->writeAttribute('w:val', $size);
			$objWriter->endElement();
			$objWriter->startElement('w:szCs');
				$objWriter->writeAttribute('w:val', $size);
			$objWriter->endElement();
		}
		
		// Bold
		if($bold) {
			$objWriter->writeElement('w:b', null);
		}
		
		// Italic
		if($italic) {
			$objWriter->writeElement('w:i', null);
			$objWriter->writeElement('w:iCs', null);
		}
		
		// Underline
		if(!is_null($underline) && $underline != 'none') {
			$objWriter->startElement('w:u');
				$objWriter->writeAttribute('w:val', $underline);
			$objWriter->endElement();
		}
		
		// Striketrough
		if($striketrough) {
			$objWriter->writeElement('w:strike', null);
		}
		
		// Foreground-Color
		if(!is_null($fgColor)) {
			$objWriter->startElement('w:highlight');
				$objWriter->writeAttribute('w:val', $fgColor);
			$objWriter->endElement();
		}
		
		$objWriter->endElement();
	}
	
	protected function _writeTextBreak(Document_Word_Writer_Shared_XMLWriter $objWriter = null) 
        {
            //echo "writing text break?";	
            $objWriter->startElement('w:r');
            $objWriter->writeElement('w:br', null);
            $objWriter->endElement();
	}
	
	protected function _writeTable(Document_Word_Writer_Shared_XMLWriter $objWriter = null, Document_Word_Writer_Section_Table $table) 
        {
            $_rows = $table->getRows();
            $_cRows = count($_rows);

            if ($_cRows > 0) {
                $cw = $table->getColumnWidths();

                $objWriter->startElement('w:tbl');
                $objWriter->startElement('w:tblPr');
                // <w:tblW w:type="dxa" w:w="9070" />
                if (!empty($cw)) {
                    $objWriter->startElement('w:tblW');
                    $objWriter->writeAttribute('w:type', 'dxa');
                    // might be auto??? if fixed...
                    $objWriter->writeAttribute('w:w', array_sum($cw));
                    $objWriter->startElement('w:tblLayout');
                    $objWriter->writeAttribute('w:type', 'fixed');
                    $objWriter->endElement(); // end tblLayout
                    $objWriter->endElement(); // end tblW
                }
               
                $tblStyle = $table->getStyle();
                
                if ($tblStyle instanceof Document_Word_Writer_Style_Table) {
                    $this->_writeTableStyle($objWriter, $tblStyle);
                } else {
                    if (!empty($tblStyle)) {
                        $objWriter->startElement('w:tblStyle');
                        $objWriter->writeAttribute('w:val', $tblStyle);
                        $objWriter->endElement();
                    }
                }

            /*
            
            <w:tblGrid>
        <w:gridCol w:w="1980" />
        <w:gridCol w:w="7030" />
      </w:tblGrid>
            */
            $objWriter->endElement();
             if (!empty($cw)) {
                $objWriter->startElement('w:tblGrid');
                foreach($table->getColumnWidths() as $w) {
                    $objWriter->startElement('w:gridCol');
                    $objWriter->writeAttribute('w:w', $w);
                    $objWriter->endElement();
                }
                $objWriter->endElement();
            } else { 
            
            
            // Fixed the cell width
                $objWriter->startElement('w:tblLayout');
                $objWriter->writeAttribute('w:type', 'fixed');
                $objWriter->endElement();
            }
            
           

            $_heights = $table->getRowHeights();
            for($i=0; $i<$_cRows; $i++) {
                $row = $_rows[$i];
                $height = $_heights[$i];

                $objWriter->startElement('w:tr');

                if(!is_null($height)) {
                        $objWriter->startElement('w:trPr');
                        $objWriter->startElement('w:trHeight');
                        $objWriter->writeAttribute('w:val', $height);
                        $objWriter->endElement();
                        $objWriter->endElement();
                }
                
                foreach($row as $cell) {
                        $objWriter->startElement('w:tc');

                        $cellStyle = $cell->getStyle();
                        $width = $cell->getWidth();
                        
                        $autoWidth = false;
                        $hasMerge = false;
                        $calcWidth = 0;
                        $merge = 0;
                        
                        if(
                                $cellStyle instanceof Document_Word_Writer_Style_Cell && 
                                isset($cellStyle->_columnNum) && 
                                isset($cellStyle->_mergeto) && 
                                ($cellStyle->_mergeto - $cellStyle->_columnNum) > 1
                        ) {
                            
//                            $hasMerge = true;
//                            $merge = $cellStyle->_mergeto - $cellStyle->_columnNum;
//                            
//                            $tblStyle = $table->getStyle();
//                            
//                            for ($i = $cellStyle->_columnNum; $i < $cellStyle->_merge; $i++){
//                                $key = '_width' . $i . '_dax';
//                                
//                                if(isset($tblStyle->{$key})){
//                                    $calcWidth += $tblStyle->{$key} * 1;
//                                    continue;
//                                }
//                                $autoWidth = true;
//                            }
                            
                        }
                        
                        if($hasMerge && $autoWidth){
                            $width = $width * $cellStyle->_merge;
                        }
                        
                        if($hasMerge && !$autoWidth){
                            $width = $calcWidth;
                        }

                        $objWriter->startElement('w:tcPr');
                        $objWriter->startElement('w:tcW');
                        $objWriter->writeAttribute('w:w', $width);
                        $objWriter->writeAttribute('w:type', 'dxa');
                        $objWriter->endElement();
                        
                        if($hasMerge){
                            $objWriter->startElement('w:gridSpan');
                            $objWriter->writeAttribute('w:val', $merge);
                            $objWriter->endElement();
                        }

                        if($cellStyle instanceof Document_Word_Writer_Style_Cell) {
                                $this->_writeCellStyle($objWriter, $cellStyle);
                        }

                        $objWriter->endElement();

                        $_elements = $cell->getElements();
                        
                        if(count($_elements) > 0) {
                                foreach($_elements as $element) {
                                        if($element instanceof Document_Word_Writer_Section_Text) {
                                                $this->_writeText($objWriter, $element);
                                        } elseif($element instanceof Document_Word_Writer_Section_TextRun) {
                                                $this->_writeTextRun($objWriter, $element);
                                        } elseif($element instanceof Document_Word_Writer_Section_Link) {
                                                $this->_writeLink($objWriter, $element);
                                        } elseif($element instanceof Document_Word_Writer_Section_TextBreak) {
                                                $this->_writeTextBreak($objWriter);
                                        } elseif($element instanceof Document_Word_Writer_Section_ListItem) {
                                                $this->_writeListItem($objWriter, $element);
                                        } elseif($element instanceof Document_Word_Writer_Section_Image ||
                                                 $element instanceof Document_Word_Writer_Section_MemoryImage) {
                                                $this->_writeImage($objWriter, $element);
                                        } elseif($element instanceof Document_Word_Writer_Section_Object) {
                                                $this->_writeObject($objWriter, $element);
                                        } elseif($element instanceof Document_Word_Writer_Section_Footer_PreserveText) {
                                                $this->_writePreserveText($objWriter, $element);
                                        }
                                }
                        } else {
                                $this->_writeTextBreak($objWriter);
                        }

                        $objWriter->endElement();
                }
                $objWriter->endElement();
			}
			$objWriter->endElement();
		}
	}
	
	protected function _writeTableStyle(Document_Word_Writer_Shared_XMLWriter $objWriter = null, Document_Word_Writer_Style_Table $style = null) 
        {
		$margins = $style->getCellMargin();
		$mTop = (!is_null($margins[0])) ? true : false;
		$mLeft = (!is_null($margins[1])) ? true : false;
		$mRight = (!is_null($margins[2])) ? true : false;
		$mBottom = (!is_null($margins[3])) ? true : false;
		 // bit of a hack..
                if (!empty($style->_fixed)) {
                    $objWriter->startElement('w:tblLayout');
                    $objWriter->writeAttribute('w:type', 'fixed');
                    $objWriter->endElement();
                }
                
		if($mTop || $mLeft || $mRight || $mBottom) {
			$objWriter->startElement('w:tblPr');
				$objWriter->startElement('w:tblCellMar');
					
					if($mTop) {
						$objWriter->startElement('w:top');
							$objWriter->writeAttribute('w:w', $margins[0]);
							$objWriter->writeAttribute('w:type', 'dxa');
						$objWriter->endElement();
					}
					
					if($mLeft) {
						$objWriter->startElement('w:left');
							$objWriter->writeAttribute('w:w', $margins[1]);
							$objWriter->writeAttribute('w:type', 'dxa');
						$objWriter->endElement();
					}
					
					if($mRight) {
						$objWriter->startElement('w:right');
							$objWriter->writeAttribute('w:w', $margins[2]);
							$objWriter->writeAttribute('w:type', 'dxa');
						$objWriter->endElement();
					}
					
					if($mBottom) {
						$objWriter->startElement('w:bottom');
							$objWriter->writeAttribute('w:w', $margins[3]);
							$objWriter->writeAttribute('w:type', 'dxa');
						$objWriter->endElement();
					}
					
				$objWriter->endElement();
			$objWriter->endElement();
		}
	}
	
	protected function _writeCellStyle(Document_Word_Writer_Shared_XMLWriter $objWriter = null, Document_Word_Writer_Style_Cell $style = null) 
        {
		$bgColor = $style->getBgColor();
		$valign = $style->getVAlign();
		$textDir = $style->getTextDirection();
		$brdSz = $style->getBorderSize();
		$brdCol = $style->getBorderColor();
		
		$bTop = (!is_null($brdSz[0])) ? true : false;
		$bLeft = (!is_null($brdSz[1])) ? true : false;
		$bRight = (!is_null($brdSz[2])) ? true : false;
		$bBottom = (!is_null($brdSz[3])) ? true : false;
		$borders = ($bTop || $bLeft || $bRight || $bBottom) ? true : false;
		
		$styles = (!is_null($bgColor) || !is_null($valign) || !is_null($textDir) || $borders) ? true : false;
		
		if($styles) {
			if(!is_null($textDir)) {
				$objWriter->startElement('w:textDirection');
					$objWriter->writeAttribute('w:val', $textDir);
				$objWriter->endElement();
			}
			
			if(!is_null($bgColor)) {
				$objWriter->startElement('w:shd');
					$objWriter->writeAttribute('w:val', 'clear');
					$objWriter->writeAttribute('w:color', 'auto');
					$objWriter->writeAttribute('w:fill', $bgColor);
				$objWriter->endElement();
			}
			
			if(!is_null($valign)) {
				$objWriter->startElement('w:vAlign');
					$objWriter->writeAttribute('w:val', $valign);
				$objWriter->endElement();
			}
			
			if($borders) {
				$_defaultColor = $style->getDefaultBorderColor();
				
				$objWriter->startElement('w:tcBorders');
					if($bTop) {
						if(is_null($brdCol[0])) { $brdCol[0] = $_defaultColor; }
						$objWriter->startElement('w:top');
							$objWriter->writeAttribute('w:val', 'single');
							$objWriter->writeAttribute('w:sz', $brdSz[0]);
							$objWriter->writeAttribute('w:color', $brdCol[0]);
						$objWriter->endElement();
					}
					
					if($bLeft) {
						if(is_null($brdCol[1])) { $brdCol[1] = $_defaultColor; }
						$objWriter->startElement('w:left');
							$objWriter->writeAttribute('w:val', 'single');
							$objWriter->writeAttribute('w:sz', $brdSz[1]);
							$objWriter->writeAttribute('w:color', $brdCol[1]);
						$objWriter->endElement();
					}
					
					if($bRight) {
						if(is_null($brdCol[2])) { $brdCol[2] = $_defaultColor; }
						$objWriter->startElement('w:right');
							$objWriter->writeAttribute('w:val', 'single');
							$objWriter->writeAttribute('w:sz', $brdSz[2]);
							$objWriter->writeAttribute('w:color', $brdCol[2]);
						$objWriter->endElement();
					}
					
					if($bBottom) {
						if(is_null($brdCol[3])) { $brdCol[3] = $_defaultColor; }
						$objWriter->startElement('w:bottom');
							$objWriter->writeAttribute('w:val', 'single');
							$objWriter->writeAttribute('w:sz', $brdSz[3]);
							$objWriter->writeAttribute('w:color', $brdCol[3]);
						$objWriter->endElement();
					}
					
				$objWriter->endElement();
			}
		}
	}
	
	function _writeImage(Document_Word_Writer_Shared_XMLWriter $objWriter, $image, $skip_para = false) 
    {
             
            static $embedid = 0;
            $embedid++;
            
            $rId = $image->getRelationId();
            $style = $image->getStyle();
            $width = $style->getWidth();
            $height = $style->getHeight();
            $align = $style->getAlign();
                    // Calculation refer to : http://startbigthinksmall.wordpress.com/2010/01/04/points-inches-and-emus-measuring-units-in-office-open-xml/
            
            // in our example - it's 1.8in wide..
            // so that was coverted to 75*1.8 = 135
            // so this get's 135 / 75 ==> 1.8
            // so this should give us 1645920;
            
            $emuWidth = ceil(($width / 75) * 914400);
            $emuHeight = ceil(($height / 75) * 914400);
                
                
                if (!$skip_para) {
                        $objWriter->startElement('w:p');
                }
                if(!is_null($align)) {
                        $objWriter->startElement('w:pPr');
                                $objWriter->startElement('w:jc');
                                        $objWriter->writeAttribute('w:val', $align);
                                $objWriter->endElement();
                        $objWriter->endElement();
               }
                 
                $objWriter->startElement('w:r');
                $objWriter->startElement('w:drawing');
                
              //  <wp:inline distB="0" distL="0" distR="0" distT="0">
           // <wp:extent cx="5486400" cy="7762875" />
          //  <wp:effectExtent b="0" l="0" r="0" t="0" />


                
                $objWriter->startElement('wp:inline');
               // $objWriter->writeAttribute('allowOverlap',1);
               //  $objWriter->writeAttribute('behindDoc',0);
                $objWriter->writeAttribute('distB',0);
                $objWriter->writeAttribute('distL',0);
                $objWriter->writeAttribute('distR',0);
                $objWriter->writeAttribute('distT',0);
                //$objWriter->writeAttribute('layoutInCell',1);
                //$objWriter->writeAttribute('locked',0);
                //$objWriter->writeAttribute('relativeHeight',0);
                //$objWriter->writeAttribute('simplePos',0);
                
                ///$objWriter->startElement('wp:simplePos');
                //$objWriter->writeAttribute('x',0);
                //$objWriter->writeAttribute('y',0);
                //$objWriter->endElement(); // End wp:simplePos
                //$objWriter->startElement('wp:positionH');
                //$objWriter->writeAttribute('relativeFrom','column');
                //$objWriter->startElement('wp:posOffset');
                // aournd 292735
                //$objWriter->text(292735);
                //$objWriter->text(0);
               // $objWriter->endElement(); // End wp:posOffset
              //  $objWriter->endElement(); // End wp:positionH
               // $objWriter->startElement('wp:positionV');
               // $objWriter->writeAttribute('relativeFrom','paragraph');
               // $objWriter->startElement('wp:posOffset');
              //  $objWriter->text(0);
                //$objWriter->text(82550);
               // $objWriter->endElement(); // End wp:posOffset
              //  $objWriter->endElement(); // End wp:positionV
                $objWriter->startElement('wp:extent');
                
                //should be aroudn 4763770 ish
                // 5.21INCHS = 4763770 == 500PIXALS
                 
                
                $objWriter->writeAttribute('cx',$emuWidth);
                $objWriter->writeAttribute('cy',$emuHeight);
                $objWriter->endElement(); // End wp:extent
                
                
                
                
                $objWriter->startElement('wp:effectExtent');
                $objWriter->writeAttribute('b',0);
                $objWriter->writeAttribute('l',0);
                $objWriter->writeAttribute('r',0);
                $objWriter->writeAttribute('t',0);
                $objWriter->endElement(); // End wp:effectExtent
                
                
                 
                $objWriter->startElement('wp:docPr');
                $objWriter->writeAttribute('descr','A description...');
                $objWriter->writeAttribute('id',$embedid);
                $objWriter->writeAttribute('name','Picture');
                $objWriter->endElement(); // End wp:docPr
                $objWriter->startElement('wp:cNvGraphicFramePr');
                $objWriter->startElement('a:graphicFrameLocks');
                $objWriter->writeAttribute('noChangeAspect',1);
                $objWriter->writeAttribute('xmlns:a','http://schemas.openxmlformats.org/drawingml/2006/main');
                $objWriter->endElement(); // End a:graphicFrameLocks
                $objWriter->endElement(); // End wp:cNvGraphicFramePr
                
                  
                
                $objWriter->startElement('a:graphic');
                $objWriter->writeAttribute('xmlns:a','http://schemas.openxmlformats.org/drawingml/2006/main');
                $objWriter->startElement('a:graphicData');
                $objWriter->writeAttribute('uri','http://schemas.openxmlformats.org/drawingml/2006/picture');
               
               
                $objWriter->startElement('pic:pic');
                $objWriter->writeAttribute('xmlns:pic','http://schemas.openxmlformats.org/drawingml/2006/picture');
                
                $objWriter->startElement('pic:nvPicPr');
                $objWriter->startElement('pic:cNvPr');
                $objWriter->writeAttribute('descr','A description...');
                $objWriter->writeAttribute('id',$embedid);
                $objWriter->writeAttribute('name','Picture');
                $objWriter->endElement(); // End pic:cNvPr
                $objWriter->startElement('pic:cNvPicPr');
                $objWriter->startElement('a:picLocks');
                $objWriter->writeAttribute('noChangeArrowheads',1);
                $objWriter->writeAttribute('noChangeAspect',1);
                $objWriter->endElement(); // End pic:picLocks
                $objWriter->endElement(); // End pic:cNvPicPr
                $objWriter->endElement(); // End pic:nvPicPr
                $objWriter->startElement('pic:blipFill');
                $objWriter->startElement('a:blip');
                $objWriter->writeAttribute('r:embed','rId' . $rId);
                $objWriter->endElement(); // End a:blip
                $objWriter->startElement('a:srcRect');
                $objWriter->endElement(); // End a:srcRect
                $objWriter->startElement('a:stretch');
                $objWriter->startElement('a:fillRect');
                $objWriter->endElement(); // End a:fillRect
                $objWriter->endElement(); // End a:stretch
                $objWriter->endElement(); // End pic:blipFill
            
                $objWriter->startElement('pic:spPr');
               
                $objWriter->writeAttribute('bwMode','auto');
                $objWriter->startElement('a:xfrm');
                $objWriter->startElement('a:off');
                $objWriter->writeAttribute('x',0);
                $objWriter->writeAttribute('y',0);
                $objWriter->endElement(); // End a:off
                $objWriter->startElement('a:ext');
                //$objWriter->writeAttribute('cx',14605);
//                $objWriter->writeAttribute('cx',ceil((4763770 / 500) * $width));
//                $objWriter->writeAttribute('cy',ceil((4763770 / 500) * $height));
                $objWriter->writeAttribute('cx',$emuWidth);
                $objWriter->writeAttribute('cy',$emuHeight);
               //$objWriter->writeAttribute('cy',14605);
                $objWriter->endElement(); // End a:ext
                $objWriter->endElement(); // End a:xfrm
                $objWriter->startElement('a:prstGeom');
                $objWriter->writeAttribute('prst','rect');
                $objWriter->startElement('a:avLst');
                $objWriter->endElement(); // End a:avLst
                $objWriter->endElement(); // End a:prstGeom
                $objWriter->startElement('a:noFill');
                $objWriter->endElement(); // End a:noFill
                 
                $objWriter->startElement('a:ln');
                $objWriter->writeAttribute('w',9525);
                $objWriter->startElement('a:noFill');
                $objWriter->endElement(); // End a:noFill
                $objWriter->startElement('a:miter');
                $objWriter->writeAttribute('lim',800000);
                $objWriter->endElement(); // End a:miter
                $objWriter->startElement('a:headEnd');
                $objWriter->endElement(); // End a:headEnd
                $objWriter->startElement('a:tailEnd');
                $objWriter->endElement(); // End a:tailEnd
                $objWriter->endElement(); // End a:ln
               
                $objWriter->endElement(); // End pic:spPr
                     
                $objWriter->endElement(); // End pic:pic
              
               
                $objWriter->endElement(); // End a:graphicData
                $objWriter->endElement(); // End a:graphic
                
                
                    
                
                
                $objWriter->endElement(); // End wp:anchor
                $objWriter->endElement(); // End w:drawing
                $objWriter->endElement(); // End w:r
                if (!$skip_para) {
                    $objWriter->endElement(); // End w:p
                }
//		$objWriter->startElement('w:p');
//		
//			
//		
//			$objWriter->startElement('w:r');
//			
//				$objWriter->startElement('w:pict');
//					
//					$objWriter->startElement('v:shape');
//						$objWriter->writeAttribute('type', '#_x0000_t75');
//						$objWriter->writeAttribute('style', 'width:'.$width.'px;height:'.$height.'px');
//						
//						$objWriter->startElement('v:imagedata');
//							$objWriter->writeAttribute('r:id', 'rId'.$rId);
//							$objWriter->writeAttribute('o:title', '');
//						$objWriter->endElement();
//					$objWriter->endElement();
//					
//				$objWriter->endElement();
//				
//			$objWriter->endElement();
//			
//		$objWriter->endElement();
	}
	
	protected function _writeWatermark(Document_Word_Writer_Shared_XMLWriter $objWriter = null, $image) 
        {
		$rId = $image->getRelationId();
		
		$style = $image->getStyle();
		$width = $style->getWidth();
		$height = $style->getHeight();
		$marginLeft = $style->getMarginLeft();
		$marginTop = $style->getMarginTop();
		
		$objWriter->startElement('w:p');
			
			$objWriter->startElement('w:r');
			
				$objWriter->startElement('w:pict');
					
					$objWriter->startElement('v:shape');
						$objWriter->writeAttribute('type', '#_x0000_t75');
						
						$strStyle = 'position:absolute;';
						$strStyle .= ' width:'.$width.'px;';
						$strStyle .= ' height:'.$height.'px;';
						if(!is_null($marginTop)) {
							$strStyle .= ' margin-top:'.$marginTop.'px;';
						}
						if(!is_null($marginLeft)) {
							$strStyle .= ' margin-left:'.$marginLeft.'px;';
						}
						
						$objWriter->writeAttribute('style', $strStyle);
						
						$objWriter->startElement('v:imagedata');
							$objWriter->writeAttribute('r:id', 'rId'.$rId);
							$objWriter->writeAttribute('o:title', '');
						$objWriter->endElement();
					$objWriter->endElement();
					
				$objWriter->endElement();
				
			$objWriter->endElement();
			
		$objWriter->endElement();
	}
	
	protected function _writeTitle(Document_Word_Writer_Shared_XMLWriter $objWriter = null, Document_Word_Writer_Section_Title $title) 
        {
		$text = htmlspecialchars($title->getText());
		$text = Document_Word_Writer_Shared_String::ControlCharacterPHP2OOXML($text);
		$anchor = $title->getAnchor();
		$bookmarkId = $title->getBookmarkId();
		$style = $title->getStyle();
		
		$objWriter->startElement('w:p');
			
			if(!empty($style)) {
				$objWriter->startElement('w:pPr');
					$objWriter->startElement('w:pStyle');
						$objWriter->writeAttribute('w:val', $style);
					$objWriter->endElement();
				$objWriter->endElement();
			}
			
			$objWriter->startElement('w:r');
				$objWriter->startElement('w:fldChar');
					$objWriter->writeAttribute('w:fldCharType', 'end');
				$objWriter->endElement();
			$objWriter->endElement();
			
			$objWriter->startElement('w:bookmarkStart');
				$objWriter->writeAttribute('w:id', $bookmarkId);
				$objWriter->writeAttribute('w:name', $anchor);
			$objWriter->endElement();
			
			$objWriter->startElement('w:r');
				$objWriter->startElement('w:t');
					$objWriter->text($text);
				$objWriter->endElement();
			$objWriter->endElement();
			
			$objWriter->startElement('w:bookmarkEnd');
				$objWriter->writeAttribute('w:id', $bookmarkId);
			$objWriter->endElement();
			
		$objWriter->endElement();
	}
}
?>