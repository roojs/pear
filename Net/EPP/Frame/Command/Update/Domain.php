<?php
    require_once 'Net/EPP/Frame/Command/Update.php';
	/**
	* @package Net_EPP
	*/
	class Net_EPP_Frame_Command_Update_Domain extends Net_EPP_Frame_Command_Update {

		function __construct($opts, $copts) {
			parent::__construct('domain', $opts, $copts);
		}
	}
?>
