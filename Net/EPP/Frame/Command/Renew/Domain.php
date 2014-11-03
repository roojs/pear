<?php
require_once 'Net/EPP/Frame/Command/Renew.php';
	/**
	* @package Net_EPP
	*/
	class Net_EPP_Frame_Command_Renew_Domain extends Net_EPP_Frame_Command_Renew {

		function __construct($opts, $copts) {
			parent::__construct('domain', $opts, $copts);
		}
	}
?>