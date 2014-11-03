<?php
    require_once 'Net/EPP/Frame/Command.php';
	/**
	* @package Net_EPP
	*/
	class Net_EPP_Frame_Command_Logout extends Net_EPP_Frame_Command {
		function __construct($params = array()) {
			parent::__construct('logout');
                        $this->addCommandParams($params);
		}
	}
 
