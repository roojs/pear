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
       if (! empty ( $config ['date'] )) {
           $this->date = $config ['date'];
       }
        
       if (! empty ( $config ['due_date'] )) {
           $this->dueDate = $config ['due_date'];
       }
       
       if (! empty ( $config ['contact_id'] )) {
           $this->contactID = $config ['contact_id'];
       }
       
       if (! empty ( $config ['currency'] )) {
           $this->currencyCode = $config ['currency'];
       }
       
       if (! empty ( $config ['line_items'] )) {
            foreach ($config ['line_items'] as $i)
            {
               $this->addLineItem($i['desc'],$i['qty'],$i['cost'],$i['code']);
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
   } 
  
   function toXMLString() 
   {
                
        return $this->toXML()->asXML();
   } 
}