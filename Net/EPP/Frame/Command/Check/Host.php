<?php
    require_once 'Net/EPP/Frame/Command/Check.php';
	/**
	* @package Net_EPP
	*/
	class Net_EPP_Frame_Command_Check_Host extends Net_EPP_Frame_Command_Check {

		function __construct($opts, $copts) {
			parent::__construct('host', $opts, $copts);
		}
	}
?>
