<?php

class Services_Xero_Invoice {
   var $Date;
	
   var $DueDate;	

   var $ContactID;
   
   var $CurrencyCode;
   
   var $InvoiceType = 'ACCREC';   
   
   var $InvoiceStatus = 'DRAFT';
   
   var $LineAmountTypes = 'Exclusive';   
   
   var $lineItems = array();   
   
   function __construct($config)
   {
       if (! empty ( $config ['date'] )) {
           $this->Date = $config ['date'];
       }
        
       if (! empty ( $config ['due_date'] )) {
           $this->DueDate = $config ['due_date'];
       }
       
       if (! empty ( $config ['contact_id'] )) {
           $this->ContactID = $config ['contact_id'];
       }
       
       if (! empty ( $config ['currency'] )) {
           $this->CurrencyCode = $config ['currency'];
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
        $this->lineItems[] = array('desc' => $desc,
                                   'qty' => $qty,
                                   'item_code' => $item_code,
                                   'acc_code' => $acc_code
                                  );  
   }   
   
   function toXML() 
   {
        $inv_xml = new SimpleXMLElement('<Invoices/>');
    	  
        $inv = $inv_xml->addChild('Invoice');
    	  
        $inv->addChild('Type',$this->InvoiceType);

        $inv->addChild('CurrencyCode',$this->CurrencyCode);

        $inv->addChild('Status',$this->InvoiceStatus);    	  

        $contact = $inv->addChild('Contact');

        $contact->addChild('ContactID',$this->ContactID);

        $inv->addChild('Date',$this->Date);

        $inv->addChild('LineAmountTypes',$this->LineAmountTypes);

        $line_items = $inv->addChild('LineItems');
        
        foreach ($this->lineItems as $u)
        {
            $item = $line_items->addChild('LineItem');

            $item->addChild('Description',$u['desc']);

            $item->addChild('Quantity',$u['qty']);

            $item->addChild('ItemCode',$u['item_code']);

            $item->addChild('AccountCode',$u['acc_code']);        	   
        }
        
        return $inv_xml;
   } 
  
   function toXMLString() 
   {
                
        return $this->toXML()->asXML();
   } 
}