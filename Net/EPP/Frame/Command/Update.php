<?php

	/**
	* @package Net_EPP
	*/
	class Net_EPP_Frame_Command_Update extends Net_EPP_Frame_Command {

		function __construct($type, $opts, $copts) {
			$this->type = $type;
			parent::__construct('update', $type, $opts, $copts);
		}
	}
?>
