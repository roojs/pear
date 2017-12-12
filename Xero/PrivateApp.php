<?php
require 'Xero/Auth/XeroOAuth.php';

define ( 'BASE_PATH', dirname(__FILE__) );
define ( "XRO_APP_TYPE", "Private" );
define ( "OAUTH_CALLBACK", "oob" );


class Xero_PrivateApp {
	var $useragent;
	
   var $signatures;	

   var $XeroOAuth;
   
   var $initialCheck;
   
   var $checkErrors;
   
   var $SSL_KeyPath;
   
   var $oauthSession;
   
   function __construct($config)
   {
   	
   	 $this->useragent = "XeroOAuth-PHP Private App Test";
   	 
       $this->signatures = array (
		     'consumer_key' => $consumer_key,
		     'shared_secret' => $shared_secret,
		     // API versions
		     'core_version' => '2.0',
		     'payroll_version' => '1.0',
		     'file_version' => '1.0' 
       );   	 
   	 
    	 if (XRO_APP_TYPE == "Private" || XRO_APP_TYPE == "Partner") {
	        $this->signatures ['rsa_private_key'] = $this->SSL_KeyPath . '/certs/privatekey.pem';
	        $this->signatures ['rsa_public_key'] = $this->SSL_KeyPath . '/certs/publickey.cer';
       }
       
       $this->XeroOAuth = new XeroOAuth ( array_merge ( array (
		      'application_type' => XRO_APP_TYPE,
		      'oauth_callback' => OAUTH_CALLBACK,
		      'user_agent' => $this->useragent 
       ), $this->signatures ) );
   }
   
   function connectXero()
   {
       $this->initialCheck = $this->XeroOAuth->diagnostics ();
       $this->checkErrors = count ( $this->initialCheck );

       if ($checkErrors > 0) {
	        return false;	        
      }
      
      $session = persistSession ( array (
			'oauth_token' => $this->XeroOAuth->config ['consumer_key'],
			'oauth_token_secret' => $this->XeroOAuth->config ['shared_secret'],
			'oauth_session_handle' => '' 
	      ) );
	   $this->oauthSession = retrieveSession ();
	
	   if (isset ( $this->oauthSession ['oauth_token'] )) {
		    $this->XeroOAuth->config ['access_token'] = $this->oauthSession ['oauth_token'];
		    $this->XeroOAuth->config ['access_token_secret'] = $this->oauthSession ['oauth_token_secret'];
		
		    //include 'tests/tests.php';
	   }
	   
   }
   
   public function getInvoiceList()
   {
       $response = $this->XeroOAuth->request('GET', $this->XeroOAuth->url('Invoices', 'core'), array('order' => 'Total DESC'));
       if ($this->XeroOAuth->response['code'] != 200) {
           // Error 
           return;      
          //outputError($XeroOAuth);
       }	
       
       $invoiceList = $this->XeroOAuth->parseResponse($this->XeroOAuth->response['response'], $this->XeroOAuth->response['format']);
       return $invoiceList;           
   }
   
   public function getInvoice($invoiceID)
   {
   	 if($invoiceID == '')
   	 {
   	     return;
   	 }
       $response = $XeroOAuth->request('GET', $XeroOAuth->url('Invoices/' . $invoiceID , 'core'));
       
       if ($this->XeroOAuth->response['code'] != 200) {
           // Error 
           return;      
          //outputError($XeroOAuth);
       }
       
       $invoice = $this->XeroOAuth->parseResponse($this->XeroOAuth->response['response'], $this->XeroOAuth->response['format']);
       return $invoice;
   }
   
}

$useragent = "XeroOAuth-PHP Private App Test";

$signatures = array (
		'consumer_key' => 'U7CCFZKXLHANQ0CUWYEPMP1LGCM837',
		'shared_secret' => '7VIDBER73TS4IM5BJYF33JEYUEV1VE',
		// API versions
		'core_version' => '2.0',
		'payroll_version' => '1.0',
		'file_version' => '1.0' 
);

if (XRO_APP_TYPE == "Private" || XRO_APP_TYPE == "Partner") {
	$signatures ['rsa_private_key'] = BASE_PATH . '/certs/privatekey.pem';
	$signatures ['rsa_public_key'] = BASE_PATH . '/certs/publickey.cer';
}

$XeroOAuth = new XeroOAuth ( array_merge ( array (
		'application_type' => XRO_APP_TYPE,
		'oauth_callback' => OAUTH_CALLBACK,
		'user_agent' => $useragent 
), $signatures ) );
include 'tests/testRunner.php';

$initialCheck = $XeroOAuth->diagnostics ();
$checkErrors = count ( $initialCheck );
if ($checkErrors > 0) {
	// you could handle any config errors here, or keep on truckin if you like to live dangerously
	foreach ( $initialCheck as $check ) {
		echo 'Error: ' . $check . PHP_EOL;
	}
} else {
	$session = persistSession ( array (
			'oauth_token' => $XeroOAuth->config ['consumer_key'],
			'oauth_token_secret' => $XeroOAuth->config ['shared_secret'],
			'oauth_session_handle' => '' 
	) );
	$oauthSession = retrieveSession ();
	
	if (isset ( $oauthSession ['oauth_token'] )) {
		$XeroOAuth->config ['access_token'] = $oauthSession ['oauth_token'];
		$XeroOAuth->config ['access_token_secret'] = $oauthSession ['oauth_token_secret'];
		
		include 'tests/tests.php';
	}
	
	echo "invoice test";
	$response = $XeroOAuth->request('GET', $XeroOAuth->url('Invoices', 'core'), array('order' => 'Total DESC'));
            if ($XeroOAuth->response['code'] == 200) {
                $invoices = $XeroOAuth->parseResponse($XeroOAuth->response['response'], $XeroOAuth->response['format']);
                echo "There are " . count($invoices->Invoices[0]). " invoices in this Xero organisation, the first one is: </br>";
                //pr($invoices->Invoices[0]->Invoice);
                print_r($invoices);
            } else {
                outputError($XeroOAuth);
            }	
	
    $response = $XeroOAuth->request('GET', $XeroOAuth->url('Invoices/765133d8-e03d-41d9-9462-6357fe36621f', 'core'));
            if ($XeroOAuth->response['code'] == 200) {
                $invoices = $XeroOAuth->parseResponse($XeroOAuth->response['response'], $XeroOAuth->response['format']);
                echo "There are " . count($invoices->Invoices[0]). " invoices in this Xero organisation, the first one is: </br>";
                //pr($invoices->Invoices[0]->Invoice);
                print_r($invoices);
            } else {
                outputError($XeroOAuth);
            }		
	
	//testLinks ();
	
}
