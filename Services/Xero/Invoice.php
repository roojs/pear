<?php

class Services_Xero_Invoice {
   var $date;
	
   var $dueDate;	

   var $contactID;

   var $invoiceID ;   
   
   var $currencyCode;
   
   var $invoiceType = 'ACCREC';   
   
   var $status = 'DRAFT';
   
   var $lineAmountTypes = 'Exclusive';   
   
    
   function __construct($config)
   {   	
       foreach ($config as $key => $val) {
           $this->$key = $val;           
       } 
       
   }
   
   
   function toXML() 
   {
        $doc = new DOMDocument('1.0', 'utf-8');   	  
        
        
        
        $element = $doc->appendChild($doc->createElement('Invoices') );  
        $inv = $element->appendChild($doc->createElement('Invoice'));
        $inv->appendChild($doc->createElement('Type',$this->invoiceType));
        
        // old invoice
        if(!empty($this->invoiceID))
        {
           $inv_id = $doc->createElement('InvoiceID',$this->invoiceID);
           $inv->appendChild($inv_id);
        } else {
            // new invoice
            $inv_date = $doc->createElement('Date',$this->date);
            $inv->appendChild($inv_date);        
        }        
    
    
        $inv->appendChild($doc->createElement('CurrencyCode',$this->currencyCode));
        $inv->appendChild($doc->createElement('Status',$this->status));
        
        $contact = $inv->appendChild($doc->createElement('Contact'));
        $contact->appendChild($doc->createElement('ContactID',$this->contactID));
        
        $inv->appendChild( $doc->createElement('LineAmountTypes',$this->lineAmountTypes) );
        
        $line_items =  $inv->appendChild($doc->createElement('LineItems'));
   	          
        foreach ($this->LineItems as $u) {        
            $item = $doc->createElement('LineItem');

            foreach ($u as $key => $val) {                
                if($val) {
                    $item->appendChild($doc->createElement($key,$val));
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