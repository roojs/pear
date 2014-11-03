<?php
    require_once 'Net/EPP/Frame/Command/Transfer.php';
	/**
	* @package Net_EPP
	*/
	class Net_EPP_Frame_Command_Transfer_Domain extends Net_EPP_Frame_Command_Transfer {

		function __construct($opts, $copts) {
                    parent::__construct('domain', $opts, $copts);
		}

		function setPeriod($period, $units='y') {
			$el = $this->createObjectPropertyElement('period');
			$el->setAttribute('unit', $units);
			$el->appendChild($this->createTextNode($period));
			$this->payload->appendChild($el);
		}

	}
?>
