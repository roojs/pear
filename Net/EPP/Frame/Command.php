<?php
    require_once 'Net/EPP/Frame.php';
    require_once 'Net/EPP/ObjectSpec.php';
	/**
	* @package Net_EPP
	*/
	class Net_EPP_Frame_Command extends Net_EPP_Frame {

		function __construct($command, $type="", $params=array(),$cparams=array()) {
			$this->type = $type;
			$command = strtolower($command);
			if (!in_array($command, array('check', 'info', 'create', 'update', 'delete', 'renew', 'transfer', 'poll', 'login', 'logout'))) trigger_error("Invalid argument value '$command' for \$command", E_USER_ERROR);
			parent::__construct('command');

			$this->command = $this->createElement($command);
			$this->body->appendChild($this->command);

			if (!empty($this->type)) {
				$this->payload = $this->createElementNS(
					Net_EPP_ObjectSpec::xmlns($this->type),
					$this->type.':'.$command
				);

				$this->command->appendChild($this->payload);
			}

			$this->clTRID = $this->createElement('clTRID');
			$this->clTRID->appendChild($this->createTextNode(''));
			$this->body->appendChild($this->clTRID);
            
            if (!empty($this->type)) {
                $this->addTypeParams($params);
                $this->addCommandParams($cparams);
            } else {
                $this->addCommandParams($params);
            }
            
		}
        
        
        function addCommandParams($params) {
            foreach($params as $k=>$v) {
                $this->setParam($k, $v);
            }
        }

        
        function addTypeParams($params) {
            foreach($params as $k=>$v) {
                $this->addObjectProperty($k, $v);
            }
        }
        
        
        /*     'id' => 'tom01',
               'postalInfo' => array(
                    '@type' =>"int",
                    
                    'name' => 'John Doe',
                    'org' => 'Example',
                    'addr' => array(
                    
                       'street*1' => '123 example dr',
                       'street*2' => 'test',
                       'city' => 'test',
                       'sp' => 'some state',
                       'pc' => '123-123-123 postcode',
                       'cc' => 'HK'
                        
                         
                    )
                
                
               ),
               'voice' => '+123 123 123',
               'fax' => '+123 123 123',
               'email'=> 'test@test.com',
               'authInfo' => array(
                    'pw' => ''
               )
               
               */
        
        
		function addObjectProperty($name, $value=NULL, $parent=false, $opts = array()) {
			// array( '*multi' => array( .....   ) )
            
            $parent = $parent ? $parent : $this->payload;
            
            list($name,) = explode('*', $name); // remove *xxx -- multiple values.
            
            $element = $this->createObjectPropertyElement($name);
			
            
            $parent->appendChild($element);
            // attributes.. and '='
            if (is_array($value)) {
                foreach($value as $k=>$v) {
                    if ($k[0] == '@') {
                        $element->setAttribute(substr($k,1), $v);
                        unset($value[$k]);
                        continue;
                    }
                    if ($k == '=') {
                        $element->appendChild($this->createTextNode($v));
                        unset($value[$k]);
                        continue;
                    }
                }
                
            }
            

			if ($value instanceof DomNode) {  /// is this used!?!?
				$element->appendChild($value);
                return $element;
            }
            
            if ((is_string($value) || is_numeric($value) )  && strlen($value)) {
				$element->appendChild($this->createTextNode($value));
                return $element;
			}
            
            if (!is_array($value) || empty($value)) {
                return $element;
            }
            // array formats:
            
            foreach($value as $k=>$v) {
                
                $this->addObjectProperty($k, $v, $element);
                
            }
            
            return $element;
            
		}

		function createObjectPropertyElement($name) {
			return $this->createElementNS(
				Net_EPP_ObjectSpec::xmlns($this->type),
				$this->type.':'.$name
			);
		}

		function createExtensionElement($ext, $command) {
			$this->extension = $this->createElement('extension');
			$this->body->appendChild($this->extension);

			$this->extension->payload = $this->createElementNS(
				Net_EPP_ObjectSpec::xmlns($ext),
				$ext.':'.$command
			);

			$this->extension->appendChild($this->extension->payload);
		}
        
        /**
         *
         *  if it's a 'type' command -- pass it to addObjectProperty
         *
         *  
         *
         *
         */
        function setParam($param, $value)
        {
            
            if (is_array($value)) {
                foreach($value as $k=>$v) {
                    
                    if (!is_array($v)) {
                        $element = $this->createElement($k);
                        $element->appendChild($this->createTextNode($v));
                        $this->$param->appendChild($element);
                        continue;
                    }
                    foreach($v as $vv) {
                        $element = $this->createElement($k);
                        $element->appendChild($this->createTextNode($vv));
                        $this->$param->appendChild($element);
                        
                    }
                     
                }
                return;
            }
            
            $this->$param->appendChild($this->createTextNode($value));
            //$this->$param->setAttribute($param, $value);
        }
	}
