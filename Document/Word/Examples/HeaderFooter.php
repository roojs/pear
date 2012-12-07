<?php
ini_set('include_path', __DIR__  . '/../../..:.');
require_once __DIR__ . '/../Writer.php';

// New Word Document
$PHPWord = new Document_Word_Writer();

// New portrait section
$section = $PHPWord->createSection();

// Add header
$header = $section->createHeader();
$table = $header->addTable();
$table->addRow();
$table->addCell(200)->addText('This is the header.');
$table->addCell(4500)->addImage('_earth.JPG', array('width'=>50, 'height'=>50, 'align'=>'right'));

// Add footer
$footer = $section->createFooter();
$footer->addPreserveText('Page {PAGE} of {NUMPAGES}.', array('align'=>'right'));

// Write some text
$section->addTextBreak();
$section->addText('Some text...');

// Save File
require_once __DIR__ . '/../Writer/IOFactory.php';
$objWriter = Document_Word_Writer_IOFactory::createWriter($PHPWord, 'Word2007');
$objWriter->save('/tmp/HeaderFooter.docx');
$fn = '/tmp/HeaderFooter.docx';
if (file_exists($fn)) {
    echo 'Prepare for download!!';
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename='.basename($fn));
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($fn));
    ob_clean();
    flush();
    readfile($fn);
    exit;
}
?>