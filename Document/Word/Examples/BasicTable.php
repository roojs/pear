<?php
ini_set('include_path', __DIR__  . '/../../..:.');
require_once __DIR__ . '/../Writer.php';
// New Word Document
$PHPWord = new Document_Word_Writer();

// New portrait section
$section = $PHPWord->createSection();

// Add table
$table = $section->addTable();

$cellStyle = array('bgColor'=>'C0C0C0');

$table = $section->addTable();
$table->addRow(1000);
$table->addCell(2000, $cellStyle)->addText('Cell 1');
$table->addCell(2000, $cellStyle)->addText('Cell 2');
$table->addCell(2000, $cellStyle)->addText('Cell 3');
$table->addRow();
$table->addCell(2000)->addText('Cell 4');
$table->addCell(2000)->addText('Cell 5');
$table->addCell(2000)->addText('Cell 6');

// Save File
require_once __DIR__ . '/../Writer/IOFactory.php';
$objWriter = Document_Word_Writer_IOFactory::createWriter($PHPWord, 'Word2007');
$objWriter->save('/tmp/BasicTable.docx');

// Download the file for testing
if($_SERVER['SERVER_NAME'] == 'localhost')
{
    exit;
}
$file = '/tmp/BasicTable.docx';
if (file_exists($file)) {
    echo 'Prepare for download!!';
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename='.basename($file));
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    ob_clean();
    flush();
    readfile($file);
    exit;
}

?>
