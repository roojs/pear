<?php

class Services_Xero {
   var $useragent;
	
   var $signatures;	

   var $XeroOAuth;
   
   var $initialCheck;
   
   var $checkErrors;
   
   var $oauthSession;
   
   var $session;
   
   var $_xero_defaults;   
   
   var $_xero_consumer_options;
   
   function __construct($config)
   {
   	
       $this->useragent = "XeroOAuth-PHP Private App Test";
       
       $this->signatures = array (
           'consumer_key' => $config['consumer_key'],
           'shared_secret' => $config['shared_secret'],
           // API versions
           'core_version' => '2.0',
           'payroll_version' => '1.0',
           'file_version' => '1.0' 
       );   	 
		
       $this->_xero_consumer_options = array (
            'request_token_path' => 'oauth/RequestToken',
            'access_token_path' => 'oauth/AccessToken',
            'authorize_path' => 'oauth/Authorize' 
       );
       
       // for public app type          	 
       $signature_method = 'HMAC-SHA1';
   	 
       if ($config['xero_app_type'] == "Private" || $config['xero_app_type'] == "Partner") {
       	
           $this->signatures ['rsa_private_key'] = $config['cert_dir'] . '/privatekey.pem';
           
           $this->signatures ['rsa_public_key'] = $config['cert_dir'] . '/publickey.cer';
           
           $signature_method = 'RSA-SHA1';
       }
       
       $this->_xero_defaults = array (
           'xero_url' => 'https://api.xero.com/',
           'site' => 'https://api.xero.com',
           'authorize_url' => 'https://api.xero.com/oauth/Authorize',
          'signature_method' => $signature_method 
       );       
             
       //require 'Xero/Auth/XeroOAuth.php';
       require 'Services/Xero/OAuth.php';
       
       $this->XeroOAuth = new Services_Xero_OAuth ( array_merge ( array (
            'application_type' => $config['xero_app_type'],
            'oauth_callback' => 'oob',
            'user_agent' => $this->useragent,
            'ca_cert_path' => $config['cert_dir'] 
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
         'oauth_token' => $this->XeroOAuth->config ['consumer_key'],
         'oauth_token_secret' => $this->XeroOAuth->config ['shared_secret'],
         'oauth_session_handle' => '' 
       ) );
       	    
       $this->oauthSession = $this->retrieveSession ();
	
       if (isset ( $this->oauthSession ['oauth_token'] )) {
           $this->XeroOAuth->config ['access_token'] = $this->oauthSession ['oauth_token'];
		     
           $this->XeroOAuth->config ['access_token_secret'] = $this->oauthSession ['oauth_token_secret'];
		
       }
       
   }
   
   
   
   

   function refreshToken()
   {
       $response = $this->XeroOAuth->refreshToken($this->oauthSession['oauth_token'], $this->oauthSession['oauth_session_handle']);
       if ($this->XeroOAuth->response['code'] != 200) {
            if ($this->XeroOAuth->response['helper'] == "TokenExpired") {
                $this->XeroOAuth->refreshToken($this->oauthSession['oauth_token'], $this->oauthSession['session_handle']);
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
           $_SESSION[__CLASS__]['access_token'] = $response['oauth_token'];
           
           $_SESSION[__CLASS__]['oauth_token_secret'] = $response['oauth_token_secret'];
           
           if(isset($response['oauth_session_handle'])) {
               $_SESSION[__CLASS__]['session_handle']     = $response['oauth_session_handle'];
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
       if (isset($_SESSION[__CLASS__]['access_token'])) {
           $response['oauth_token']            =    $_SESSION[__CLASS__]['access_token'];
           
           $response['oauth_token_secret']     =    $_SESSION[__CLASS__]['oauth_token_secret'];
           
           $response['oauth_session_handle']   =    $_SESSION[__CLASS__]['session_handle'];
           
           return $response;
       }       
       
       return false;

   }   
   
   public function getInvoiceList()
   {
       $response = $this->XeroOAuth->request('GET', $this->XeroOAuth->url('Invoices', 'core'), array('order' => 'Total DESC'));
       if ($this->XeroOAuth->response['code'] != 200) {
                       
           throw new Exception('Xero Error: ' . $this->XeroOAuth->response['response']);
           
           return;                
       }	
       
       $invoiceList = $this->XeroOAuth->parseResponse($this->XeroOAuth->response['response'], $this->XeroOAuth->response['format']);
       return $invoiceList;           
   }
   
   public function getInvoiceByFilter($param)
   {
       if(empty($param)) {
           return;
       }
       
       $response = $this->XeroOAuth->request('GET', $this->XeroOAuth->url('Invoices' , 'core'), $param ,'','xml');
       
       if ($this->XeroOAuth->response['code'] != 200) {
       	 
           throw new Exception('Xero Error: ' . $this->XeroOAuth->response['response']);
                 
           return;      
          //outputError($XeroOAuth);
       }
       
       $invoice = $this->XeroOAuth->parseResponse($this->XeroOAuth->response['response'], $this->XeroOAuth->response['format']);
       return $invoice;
   }   
   
   public function getInvoice($invoiceID)
   {
       if($invoiceID == '') {
           return;
       }
       $param = array();
       $response = $this->XeroOAuth->request('GET', $this->XeroOAuth->url('Invoices/' . $invoiceID , 'core'), $param ,'','json');
       
       if ($this->XeroOAuth->response['code'] != 200) {
       	 
           throw new Exception('Xero Error: ' . $this->XeroOAuth->response['response']);
                 
           return;      
          //outputError($XeroOAuth);
       }
       
       $invoice = $this->XeroOAuth->parseResponse($this->XeroOAuth->response['response'], $this->XeroOAuth->response['format']);
       return $invoice;
   }
   
   public function getContact($name)
   {
       if($name == '') {
           return;
       }
       
       $param = array('Where' => 'Name="'. $name. '"');
       
       $response = $this->XeroOAuth->request('GET', $this->XeroOAuth->url('Contacts' , 'core'), $param ,'','json');
       
       if ($this->XeroOAuth->response['code'] != 200) {
            
           throw new Exception('Xero Error: ' . $this->XeroOAuth->response['response']);
           
           return;      
       }
       
       $contact = $this->XeroOAuth->parseResponse($this->XeroOAuth->response['response'], $this->XeroOAuth->response['format']);       
       return $contact;
   }
   
   
   public function getContactID($name)
   {
   	
       $contact = $this->getContact($name);
    	 
       if(!count($contact->Contacts[0])) {
           return;
       }    
	     
       return  $contact->Contacts[0]->ContactID;
   }
   
   public function createInvoice($inv)
   {
      if($inv == '') {
           return;
      }
      
      $response = $this->XeroOAuth->request('POST', $this->XeroOAuth->url('Invoices', 'core'), array(), $inv->toXMLString(),'json');
   	
      if ($this->XeroOAuth->response['code'] != 200) {
           // Error
        	   
           throw new Exception('Xero Error: ' . $this->XeroOAuth->response['response']);
           
           return;
                 
      }
            
      $invoice = $this->XeroOAuth->parseResponse($this->XeroOAuth->response['response'], $this->XeroOAuth->response['format']);
      
      return $invoice;           
     
   }
}

