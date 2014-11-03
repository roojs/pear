<?php
        require_once 'Net/EPP/Frame/Command/Update.php';
	/**
	* @package Net_EPP
	*/
	class Net_EPP_Frame_Command_Update_Contact extends Net_EPP_Frame_Command_Update {

		function __construct($opts, $copts) {
			parent::__construct('contact', $opts, $copts);
		}
	}
?>
