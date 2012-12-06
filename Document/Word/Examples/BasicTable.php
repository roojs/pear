<?php
ini_set('include_path', __DIR__  . '/../../..:.');
require_once __DIR__ . '/../Writer.php';
// New Word Document
$PHPWord = new Document_Word_Writer();

// New portrait section
$section = $PHPWord->createSection();

// Add table
$table = $section->addTable();

//for($r = 1; $r <= 10; $r++) { // Loop through rows
//	// Add row
//	$table->addRow();
//	
//	for($c = 1; $c <= 5; $c++) { // Loop through cells
//		// Add Cell
//		$table->addCell(1750)->addText("Row $r, Cell $c");
//	}
//}
$table->addRow(200);
$table->addCell(300)->addText('ffffffffffffffffffffffffffffffffffffffffffffff');
$table->addCell(100)->addText('dddddddddddddd');
$table->addRow(200);
$table->addCell(300)->addText('ffffffffffffffffffffffffffffffffffffffffffffff');
$table->addCell(100)->addText('dddddddddddddd');
// Save File
require_once __DIR__ . '/../Writer/IOFactory.php';
$objWriter = Document_Word_Writer_IOFactory::createWriter($PHPWord, 'Word2007');
$objWriter->save('/tmp/BasicTable.docx');

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
