<?php
    require_once 'Net/EPP/Frame/Command/Transfer.php';
	/**
	* @package Net_EPP
	*/
	class Net_EPP_Frame_Command_Transfer_Contact extends Net_EPP_Frame_Command_Transfer {

		function __construct() {
			parent::__construct('contact');
		}
	}
?>
