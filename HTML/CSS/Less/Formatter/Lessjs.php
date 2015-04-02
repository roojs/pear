<?php
require_once 'Classic.php';

class HTML_CSS_Less_Formatter_Lessjs extends HTML_CSS_Less_Formatter_Classic {

	public $disableSingle = true;
	public $breakSelectors = true;
	public $assignSeparator = ": ";
	public $selectorSeparator = ",";
}
