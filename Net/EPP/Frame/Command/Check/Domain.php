<?php
    require_once 'Net/EPP/Frame/Command/Check.php';
	/**
	* @package Net_EPP
	*/
	class Net_EPP_Frame_Command_Check_Domain extends Net_EPP_Frame_Command_Check {

		function __construct($opts, $copts) {
			parent::__construct('domain', $opts, $copts);
                        //not in used??
//            $this->addParams($opts);
            
            
		}
        function addDomain($domain) {
            
            
            $this->addObjectProperty('name', $domain);
            //$this->command->appendChild($this->createElement('domain:name',$domain));
            

	 
        }

        
	}
 