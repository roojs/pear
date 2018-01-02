<?php

class Services_Xero_Invoice {
   var $date;
	
   var $dueDate;	

   var $contactID;

   var $invoiceID ;   
   
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
   
   function addLineItem($description,$quantity,$itemCode,$accountCode, $lineItemID)
   {
        $this->lineItems[] = array('Description' => $description,
                                   'Quantity' => $quantity,
                                   'ItemCode' => $itemCode,
                                   'AccountCode' => $accountCode,
                                   'LineItemID' => $lineItemID
                                  );  
   }   
   
   function toXML() 
   {
        $doc = new DOMDocument('1.0', 'utf-8');   	  
        
        $element = $doc->createElement('Invoices');
        $doc->appendChild($element);  
   	  
        $inv = $doc->createElement('Invoice');
   	  
        $inv_type = $doc->createElement('Type',$this->invoiceType);

        if(!empty($this->invoiceID))
        {
           $inv_id = $doc->createElement('InvoiceID',$this->invoiceID);
        }
   	  
        $inv_curr = $doc->createElement('CurrencyCode',$this->currencyCode);
   	  
        $inv_status = $doc->createElement('Status',$this->invoiceStatus);
           	  
        if(!empty($this->date))
        {          	  
            $inv_date = $doc->createElement('Date',$this->date);
        }
   	  
        $line_item_types = $doc->createElement('LineAmountTypes',$this->lineAmountTypes);   	  
   	  
        $line_items = $doc->createElement('LineItems');
   	  
        $contact = $doc->createElement('Contact');
   	     	  
        $contact_id = $doc->createElement('ContactID',$this->contactID);   	  
   	    	     	  
        $contact->appendChild($contact_id);   	  
   	  
        $element->appendChild($inv);
        
        $inv->appendChild($inv_type);
        
        if(!empty($this->invoiceID))
        {
        	  $inv->appendChild($inv_id);           
        }
                
        $inv->appendChild($inv_curr);
        
        $inv->appendChild($inv_status);
        
        $inv->appendChild($contact);
        
        if(!empty($this->date))
        {
            $inv->appendChild($inv_date);
        }
           	  
        $inv->appendChild($line_item_types);
        
        $inv->appendChild($line_items);
   	          
        foreach ($this->lineItems as $u) {        
            $item = $doc->createElement('LineItem');

            foreach ($u as $key => $val) {
                $el = $doc->createElement($key,$val);
                if($val) {
                    $item->appendChild($el);                
                }                
            } 
            $line_items->appendChild($item);
        }   	  
   	  
        return $doc;   
   	  
   } 
  
   function toXMLString() 
   {
                
        return $this->toXML()->saveXML();
   } 
}