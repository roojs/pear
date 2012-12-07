<?php
ini_set('include_path', __DIR__  . '/../../..:.');
require_once __DIR__ . '/../AbiToDocx.php';

//Example AbiWord file
$abiFileName = __DIR__ . '/../../../../../Documents/ExportBookWord.abw';
$fn = '/tmp/abiword.docx';
$conv = new File_Convert_AbiToDocx($abiFileName);
$conv->save($fn);
//$xml = new XMLReader();

// Download the file for testing
if($_SERVER['SERVER_NAME'] == 'localhost')
{
    exit;
}
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
