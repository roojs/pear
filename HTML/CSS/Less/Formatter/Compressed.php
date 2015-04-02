<?php

// see HTML_CSS_Less
require_once 'Classic.php';

class HTML_CSS_Less_Formatter_Compressed extends HTML_CSS_Less_Formatter_Classic {
	public $disableSingle = true;
	public $open = "{";
	public $selectorSeparator = ",";
	public $assignSeparator = ":";
	public $break = "";
	public $compressColors = true;

	public function indentStr($n = 0) {
		return "";
	}
}
