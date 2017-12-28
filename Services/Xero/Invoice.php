<?php

class Services_Xero_Invoice {
   var $date;
	
   var $dueDate;	

   var $contactID;
   
   var $currencyCode;
   
   var $invoiceType = 'ACCREC';   
   
   var $invoiceStatus = 'DRAFT';
   
   var $lineAmountTypes = 'Exclusive';   
   
   var $lineItems = array();   
   
   function __construct($config)
   {   	
       foreach ($config as $key => $val) {
           $this->$key = $val;           
       } 
       
       if (! empty ( $config ['line_items'] )) {
            foreach ($config ['line_items'] as $i)
            {
               $this->addLineItem($i['Description'],$i['Quantity'],$i['ItemCode'],$i['AccountCode']);
            }
       }
   }
   
   function addLineItem($desc,$qty,$item_code,$acc_code)
   {
        $this->lineItems[] = array('Description' => $desc,
                                   'Quantity' => $qty,
                                   'ItemCode' => $item_code,
                                   'AccountCode' => $acc_code
                                  );  
   }   
   
   function toXML() 
   {
        $doc = new DOMDocument('1.0', 'utf-8');   	  
        
        $element = $doc->createElement('Invoices');
        $doc->appendChild($element);  
   	  
        $inv = $doc->createElement('Invoice');
   	  
        $inv_type = $doc->createElement('Type',$this->invoiceType);
   	  
        $inv_curr = $doc->createElement('CurrencyCode',$this->currencyCode);
   	  
        $inv_status = $doc->createElement('Status',$this->invoiceStatus);   	  
          	  
   	  $inv_date = $doc->createElement('Date',$this->date);
   	  
        $line_item_types = $doc->createElement('LineAmountTypes',$this->lineAmountTypes);   	  
   	  
   	  $line_items = $doc->createElement('LineItems');
   	  
   	  $contact = $doc->createElement('Contact');
   	     	  
        $contact_id = $doc->createElement('ContactID',$this->contactID);   	  
   	    	     	  
        $contact->appendChild($contact_id);   	  
   	  
        $element->appendChild($inv);
        
        $inv->appendChild($inv_type);
        
        $inv->appendChild($inv_curr);
        
        $inv->appendChild($inv_status);
        
        $inv->appendChild($contact);
        
        $inv->appendChild($inv_date);
           	  
        $inv->appendChild($line_item_types);
        
        $inv->appendChild($line_items);
   	          
        foreach ($this->lineItems as $u) {        
            $item = $doc->createElement('LineItem');

            foreach ($u as $key => $val) {
            	 $el = $doc->createElement($key,$val);
            	 $item->appendChild($el);                
            } 
            $line_items->appendChild($item);
        }   	  
   	  
   	  return $doc;   
   	  /*      
        print_r($doc->saveXML()); exit;   	  
   	  
        $inv_xml = new SimpleXMLElement('<Invoices/>');
    	  
        $inv = $inv_xml->addChild('Invoice');
    	  
        $inv->addChild('Type',$this->invoiceType);

        $inv->addChild('CurrencyCode',$this->currencyCode);

        $inv->addChild('Status',$this->invoiceStatus);    	  

        $contact = $inv->addChild('Contact');

        $contact->addChild('ContactID',$this->contactID);

        $inv->addChild('Date',$this->date);

        $inv->addChild('LineAmountTypes',$this->lineAmountTypes);

        $line_items = $inv->addChild('LineItems');
        
        foreach ($this->lineItems as $u) {        
            $item = $line_items->addChild('LineItem');

            foreach ($u as $key => $val) {
                $item->addChild($key,$val);
            } 

        }
        
        return $inv_xml;
        */
   } 
  
   function toXMLString() 
   {
                
        return $this->toXML()->saveXML();
   } 
}