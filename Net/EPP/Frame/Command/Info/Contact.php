<?php
        require_once 'Net/EPP/Frame/Command/Info.php';
	/**
	* @package Net_EPP
	*/
	class Net_EPP_Frame_Command_Info_Contact extends Net_EPP_Frame_Command_Info {

		function __construct($opts,$copts) {
			parent::__construct('contact', $opts,$copts);
		}
	}
?>
