<?php

class Services_Xero_Invoice {
   var $Date;
	
   var $DueDate;	

   var $ContactID;
   
   var $CurrencyCode;
   
   var $InvoiceType = 'ACCREC';   
   
   var $InvoiceStatus = 'DRAFT';
   
   var $lineItems = array();   
   
   function __construct($config)
   {
   	
       
       
   }
   
   function toXML() {
        $inv_xml = new SimpleXMLElement('<Invoices/>');
    	  
        $inv = $inv_xml->addChild('Invoice');
    	  
        $inv->addChild('Type',$this->InvoiceType);

        $inv->addChild('CurrencyCode',$this->CurrencyCode);

        $inv->addChild('Status',$this->InvoiceStatus);    	  

        $contact = $inv->addChild('Contact');

        $contact->addChild('ContactID',$this->ContactID);

        $inv->addChild('Date',$this->Date);

        $inv->addChild('LineAmountTypes','Exclusive');

        $line_items = $inv->addChild('LineItems');
   } 
  
}