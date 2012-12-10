<?php
ini_set('include_path', __DIR__  . '/../../..:.');
require_once __DIR__ . '/../Writer.php';

// New Word Document
$PHPWord = new Document_Word_Writer();

// New portrait section
$section = $PHPWord->createSection();

// Add image elements
//$section->addImage('_mars.jpg');
//$section->addTextBreak(2);
$section->addText('Below is earth image!'); 
$section->addTextBreak(2);
$section->addImage('_earth.JPG', array('width'=>210, 'height'=>210, 'align'=>'center'));
$section->addTextBreak(2);
$section->addText('Above is earth image!');
//$section->addImage('_mars.jpg');

// add in /tmp/67219.0.jpg
//$rid = $section->addImageDefered('_earth.JPG', array('width'=>210, 'height'=>210, 'align'=>'center'));
// store map [67219.0] = $rid

// when get image - > lookup map [67219.0] for rid
//$section->addImageToCollection($rid, '_earth.JPG'); // 67219.0.jpg/:;:

// Save File
require_once __DIR__ . '/../Writer/IOFactory.php';
$objWriter = Document_Word_Writer_IOFactory::createWriter($PHPWord, 'Word2007');
$objWriter->save('/tmp/Image.docx');
?>
