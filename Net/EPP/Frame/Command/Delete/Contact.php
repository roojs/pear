<?php
    require_once 'Net/EPP/Frame/Command/Delete.php';
	/**
	* @package Net_EPP
	*/
	class Net_EPP_Frame_Command_Delete_Contact extends Net_EPP_Frame_Command_Delete {

		function __construct($opts,$copts) {
			parent::__construct('contact', $opts,$copts);
		}
	}
?>
