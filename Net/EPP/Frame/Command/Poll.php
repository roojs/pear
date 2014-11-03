<?php

	/**
	* @package Net_EPP
	*/
	abstract class Net_EPP_Frame_Command_Poll extends Net_EPP_Frame_Command {

                function __construct($type, $opts, $copts) {
			$this->type = $type;
                        $op = false;
                        if(isset($opts['@op'])){
                            $op = $opts['@op'];
                            unset($opts['@op']);
                        }

                        parent::__construct('poll', $type, $opts, $copts);

                        if($op){
                            $this->setOp($op);
                        }
			
		}
		function setOp($op) {
			$this->command->setAttribute('op', $op);
		}

	}
?>
