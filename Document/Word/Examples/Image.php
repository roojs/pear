<?php
ini_set('include_path', __DIR__  . '/../../..:.');
require_once __DIR__ . '/../Writer.php';

// New Word Document
$PHPWord = new Document_Word_Writer();

$section = $PHPWord->createSection();
$section->addText('I am placed on a default section.');
$section->addTextBreak(2);
// Add image elements
$section->addImage('_mars.jpg');
$section->addTextBreak(2);
// New landscape section

$section->addText('I am placed on a landscape section. Every page starting from this section will be landscape style.');
$section->addPageBreak();
$section->addImage('_mars.jpg');
$section->addTextBreak(2);
$section->addPageBreak();

// New portrait section

$section->addText('This section uses other margins.');


$section->addImage('_mars.jpg');



// Save File
require_once __DIR__ . '/../Writer/IOFactory.php';
$objWriter = Document_Word_Writer_IOFactory::createWriter($PHPWord, 'Word2007');
$objWriter->save('/tmp/Image.docx');
?>