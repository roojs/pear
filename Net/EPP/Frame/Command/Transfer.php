<?php

	/**
	* @package Net_EPP
	*/
	abstract class Net_EPP_Frame_Command_Transfer extends Net_EPP_Frame_Command {

		function __construct($type, $opts, $copts) {
//                    print_r($opts);
//                    print_r($copts);
                    $op = false;
                    if(isset($opts['@op'])){
                        $op = $opts['@op'];
                        unset($opts['@op']);
                    }
                    
                    parent::__construct('transfer', $type, $opts, $copts);
                    
                    if($op){
                        $this->setOp($op);
                    }
		}

		function setObject($object) {
			$type = strtolower(str_replace(__CLASS__.'_', '', get_class($this)));
			foreach ($this->payload->childNodes as $child) $this->payload->removeChild($child);
			$this->payload->appendChild($this->createElementNS(
				Net_EPP_ObjectSpec::xmlns($type),
				$type.':'.Net_EPP_ObjectSpec::id($type),
				$object
			));
		}

		function setOp($op) {
			$this->command->setAttribute('op', $op);
		}

		function setAuthInfo($authInfo) {
			$el = $this->createObjectPropertyElement('authInfo');
			$el->appendChild($this->createObjectPropertyElement('pw'));
			$el->firstChild->appendChild($this->createTextNode($authInfo));
			$this->payload->appendChild($el);
		}
	}
?>
