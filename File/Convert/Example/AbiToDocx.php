<?php
ini_set('include_path', __DIR__  . '/../../..:.');
require_once __DIR__ . '/../AbiToDocx.php';

//Example AbiWord file
$abiFileName = __DIR__ . '/../../../../../Documents/146-test.abw';

$conv = new File_Convert_AbiToDocx();
$conv->save($abiFileName);
//$xml = new XMLReader();




?>
