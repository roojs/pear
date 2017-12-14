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
   
   var $session;
   
   function __construct($config)
   {
   	
   	 $this->useragent = "XeroOAuth-PHP Private App Test";
   	 
       $this->SSL_KeyPath = BASE_PATH;    	 
       
       //$consumer_key = 'U7CCFZKXLHANQ0CUWYEPMP1LGCM837';
          	 
   	 //$shared_secret = '7VIDBER73TS4IM5BJYF33JEYUEV1VE';
   	 
       $this->signatures = array (
		     'consumer_key' => $config['consumer_key'],
		     'shared_secret' => $config['shared_secret'],
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
       
       $this->connectXero();
   }
   
   function connectXero()
   {
       $this->initialCheck = $this->XeroOAuth->diagnostics ();
       $this->checkErrors = count ( $this->initialCheck );

       if ($this->checkErrors > 0) {
	        return false;	        
       }
      
       $this->session = $this->persistSession ( array (
			'xero_oauth_token' => $this->XeroOAuth->config ['consumer_key'],
			'xero_oauth_token_secret' => $this->XeroOAuth->config ['shared_secret'],
			'xero_oauth_session_handle' => '' 
	    ) );
	    
	    $this->oauthSession = $this->retrieveSession ();
	
	    if (isset ( $this->oauthSession ['xero_oauth_token'] )) {
		     $this->XeroOAuth->config ['access_token'] = $this->oauthSession ['xero_oauth_token'];
		     
		     $this->XeroOAuth->config ['access_token_secret'] = $this->oauthSession ['xero_oauth_token_secret'];
		
		    //include 'tests/tests.php';
	    }
	   
   }
   
   function refreshToken()
   {
       $response = $this->XeroOAuth->refreshToken($this->oauthSession['xero_oauth_token'], $this->oauthSession['xero_oauth_session_handle']);
       if ($this->XeroOAuth->response['code'] != 200) {
            if ($this->XeroOAuth->response['helper'] == "TokenExpired") 
            {
                $this->XeroOAuth->refreshToken($this->oauthSession['xero_oauth_token'], $this->oauthSession['xero_session_handle']);
            }
            
            return false;
            
       }
       
       $this->session = $this->persistSession($response);
       $this->oauthSession = $this->retrieveSession();
       
       return true;
   }
    /**
     * Persist the OAuth access token and session handle somewhere
     * In my example I am just using the session, but in real world, this is should be a storage engine
     *
     * @param array $params the response parameters as an array of key=value pairs
     */
   function persistSession($response)
   {
       if (isset($response)) {
           $_SESSION['xero_access_token']       = $response['xero_oauth_token'];
           
           $_SESSION['xero_oauth_token_secret'] = $response['xero_oauth_token_secret'];
           
      	  if(isset($response['xero_oauth_session_handle'])) 
      	  {
      	      $_SESSION['xero_session_handle']     = $response['xero_oauth_session_handle'];
      	  } 
       } else {
        return false;
       }

   }

   /**
    * Retrieve the OAuth access token and session handle
    * In my example I am just using the session, but in real world, this is should be a storage engine
    *
    */
   function retrieveSession()
   {
       if (isset($_SESSION['xero_access_token'])) {
           $response['xero_oauth_token']            =    $_SESSION['xero_access_token'];
           
           $response['xero_oauth_token_secret']     =    $_SESSION['xero_oauth_token_secret'];
           
           $response['xero_oauth_session_handle']   =    $_SESSION['xero_session_handle'];
           
           return $response;
       }       
       
       return false;

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
   	 $param = array();
       $response = $this->XeroOAuth->request('GET', $this->XeroOAuth->url('Invoices/' . $invoiceID , 'core'), $param ,'','json');
       
       if ($this->XeroOAuth->response['code'] != 200) {
           // Error 
           return;      
          //outputError($XeroOAuth);
       }
       
       $invoice = $this->XeroOAuth->parseResponse($this->XeroOAuth->response['response'], $this->XeroOAuth->response['format']);
       return $invoice;
   }
   
   public function getContact($email)
   {
   	 if($email == '')
   	 {
   	     return;
   	 }
   	 $param = array('Where' => 'EmailAddress="'. $email. '"');
       $response = $this->XeroOAuth->request('GET', $this->XeroOAuth->url('Contacts' , 'core'), $param ,'','json');
       
       if ($this->XeroOAuth->response['code'] != 200) {
           // Error 
           return;      
          //outputError($XeroOAuth);
       }
       
       $contact = $this->XeroOAuth->parseResponse($this->XeroOAuth->response['response'], $this->XeroOAuth->response['format']);
       return $contact;
   }
   
   
   public function getContactID($email){
   	
   	 $contact = $this->getContact($email);
    	
       if(!count($contact->Contacts[0]))
  	    {
  		     return;
	    }    
	     
	    return  $contact->Contacts[0]->ContactID;
   }
   
   public function createInvoice($xml)
   {
   	if($xml == '')
   	{
   	     return;
   	}
   	$response = $this->XeroOAuth->request('POST', $this->XeroOAuth->url('Invoices', 'core'), array(), $xml);
   	
   	if ($this->XeroOAuth->response['code'] != 200) {
           // Error 
           return;      
      }
            
      $invoice = $this->XeroOAuth->parseResponse($this->XeroOAuth->response['response'], $this->XeroOAuth->response['format']);
      
      return $invoice;           
     
   }
}

