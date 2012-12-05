<?php
ini_set('include_path', __DIR__  . '/../../..:.');
require_once __DIR__ . '/../Writer.php';
require_once __DIR__ . '/../Writer/Style/Font.php';

// New Word Document
$PHPWord = new Document_Word_Writer();

// New portrait section
$section = $PHPWord->createSection();

// Add style definitions
$PHPWord->addParagraphStyle('pStyle', array('spacing'=>100));
$PHPWord->addFontStyle('BoldText', array('bold'=>true));
$PHPWord->addFontStyle('ColoredText', array('color'=>'FF8080'));
$PHPWord->addLinkStyle('NLink', array('color'=>'0000FF', 'underline'=>Document_Word_Writer_Style_Font::UNDERLINE_SINGLE));

// Add text elements
$textrun = $section->createTextRun('pStyle');

$textrun->addText('Each textrun can contain native text or link elements.');
$textrun->addText(' No break is placed after adding an element.', 'BoldText');
$textrun->addText(' All elements are placed inside a paragraph with the optionally given p-Style.', 'ColoredText');
$textrun->addText(' The best search engine: ');
$textrun->addLink('http://www.google.com', null, 'NLink');
$textrun->addText('. Also not bad: ');
$textrun->addLink('http://www.bing.com', null, 'NLink');



// Save File
require_once __DIR__ . '/../Writer/IOFactory.php';
$objWriter = Document_Word_Writer_IOFactory::createWriter($PHPWord, 'Word2007');
$objWriter->save('/tmp/Textrun.docx');
?>