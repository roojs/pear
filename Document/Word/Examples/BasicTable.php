<?php
ini_set('include_path', __DIR__  . '/../../..:.');
require_once __DIR__ . '/../Writer.php';
// New Word Document
$PHPWord = new Document_Word_Writer();

// New portrait section
$section = $PHPWord->createSection();

// Add table
$table = $section->addTable();

for($r = 1; $r <= 10; $r++) { // Loop through rows
	// Add row
	$table->addRow();
	
	for($c = 1; $c <= 5; $c++) { // Loop through cells
		// Add Cell
		$table->addCell(1750)->addText("Row $r, Cell $c");
	}
}

// Save File
require_once __DIR__ . '/../Writer/IOFactory.php';
$objWriter = Document_Word_Writer_IOFactory::createWriter($PHPWord, 'Word2007');
$objWriter->save('/tmp/BasicTable.docx');
?>
