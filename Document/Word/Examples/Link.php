<?php
ini_set('include_path', __DIR__  . '/../../..:.');
require_once __DIR__ . '/../Writer.php';
require_once __DIR__ . '/../Writer/Style/Font.php';

// New Word Document
$PHPWord = new Document_Word_Writer();

// New portrait section
$section = $PHPWord->createSection();

// Add hyperlink elements
$section->addLink('http://www.google.com', 'Best search engine', array('color'=>'0000FF', 'underline'=>Document_Word_Writer_Style_Font::UNDERLINE_SINGLE));
$section->addTextBreak(2);

$PHPWord->addLinkStyle('myOwnLinkStyle', array('bold'=>true, 'color'=>'808000'));
$section->addLink('http://www.bing.com', null, 'myOwnLinkStyle');
$section->addLink('http://www.yahoo.com', null, 'myOwnLinkStyle');




// Save File
require_once __DIR__ . '/../Writer/IOFactory.php';
$objWriter = Document_Word_Writer_IOFactory::createWriter($PHPWord, 'Word2007');
$objWriter->save('/tmp/Link.docx');
?>