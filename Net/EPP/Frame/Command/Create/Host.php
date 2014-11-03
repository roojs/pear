<?php
require_once 'Net/EPP/Frame/Command/Create.php';
	/**
	* @package Net_EPP
	*/
	class Net_EPP_Frame_Command_Create_Host extends Net_EPP_Frame_Command_Create {

		function __construct($opts, $copts) {
			parent::__construct('host', $opts,$copts);
            
		}
	}
?>
