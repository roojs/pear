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
   
   var $_nonce_chars;
   
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
       
       $this->_nonce_chars="0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
       
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
      
       $this->_xero_curl_options = array ( 
            'curl_connecttimeout' => 30,
            'curl_timeout' => 20,
            // for security you may want to set this to TRUE. If you do you need
            // to install the servers certificate in your local certificate store.
            'curl_ssl_verifypeer' => 2,
            // include ca-bundle.crt from http://curl.haxx.se/ca/cacert.pem
            'curl_cainfo' => $config['cert_dir'] . '/ca-bundle.crt',
            'curl_followlocation' => false, // whether to follow redirects or not
                                            // TRUE/1 is not a valid ssl verifyhost value with curl >= 7.28.1 and 2 is more secure as well.
                                            // More details here: http://php.net/manual/en/function.curl-setopt.php
            'curl_ssl_verifyhost' => 2,
            // support for proxy servers
            'curl_proxy' => false, // really you don't want to use this if you are using streaming
            'curl_proxyuserpwd' => false, // format username:password for proxy, if required
            'curl_encoding' => '', // leave blank for all supported formats, else use gzip, deflate, identity
            'curl_verbose' => true 
       );
		
       $this->config = array_merge ( $this->_xero_defaults, $this->_xero_consumer_options, $this->_xero_curl_options, $config );       
       
       //$initialCheck = $this->cert_check();
       
       //$this->checkErrors = count ( $this->cert_check());

       if (count ( $this->cert_check()) > 0) {
           require 'PEAR/Exception.php';
            
           throw new PEAR_Exception('Xero Error: SSL Cert problem');
           return false;	        
       }       
       
       print_r($this->config);       
              
       require 'Xero/Auth/XeroOAuth.php';
       
       $this->XeroOAuth = new XeroOAuth ( array_merge ( array (
            'application_type' => $config['xero_app_type'],
            'oauth_callback' => 'oob',
            'user_agent' => $this->useragent,
            'ca_cert_path' => $config['cert_dir'] 
       ), $this->signatures ) );
       
       $this->connectXero();
   }
   
   function connectXero()
   {
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
   
   function cert_check()
   {
       $r = array ();
   	 
       if ($this->config ['xero_app_type'] == 'Public') {
           return $r;
       }
   	 
       if (! file_exists ( $this->signatures ['rsa_public_key'] )) {
           $r ['rsa_cert_error'] = "Can't read the self-signed SSL cert. Private and Partner API applications require a self-signed X509 cert http://developer.xero.com/documentation/advanced-docs/public-private-keypair/ \n";           
       }

           
       if (file_exists ( $this->signatures ['rsa_public_key'] )) {
           $data = openssl_x509_parse ( file_get_contents ( $this->signatures ['rsa_public_key'] ) );
              
           $validFrom = date ( 'Y-m-d H:i:s', $data ['validFrom_time_t'] );
               
           if (time () < $data ['validFrom_time_t']) {
               $r ['ssl_cert_error'] = "Application cert not yet valid - cert valid from " . $validFrom . "\n";
           }
               
           $validTo = date ( 'Y-m-d H:i:s', $data ['validTo_time_t'] );
              
           if (time () > $data ['validTo_time_t']) {
              $r ['ssl_cert_error'] = "Application cert cert expired - cert valid to " . $validFrom . "\n";
           }
       }
         
       if (! file_exists ( $this->signatures ['rsa_private_key'] )) {
           $r ['rsa_cert_error'] = "Can't read the self-signed cert key. Check your rsa_private_key config variable. Private and Partner API applications require a self-signed X509 cert http://developer.xero.com/documentation/advanced-docs/public-private-keypair/ \n";         
       }

       if (file_exists ( $this->signatures ['rsa_private_key'] )) {
         	
           $cert_content = file_get_contents ( $this->signatures ['rsa_public_key'] );
            
           $priv_key_content = file_get_contents ( $this->signatures ['rsa_private_key'] );
            
           if (! openssl_x509_check_private_key ( $cert_content, $priv_key_content )) {
               $r ['rsa_cert_error'] = "Application certificate and key do not match \n";            
           }

       }   	 
       
       return $r;
   }   
   
   function url($request, $api = "core") 
   {
      switch ($request) {
          case "RequestToken" :
              $this->config ['host'] = $this->config ['site'] . '/oauth/';     
              break;
              
          case "Authorize" :
              $this->config ['host'] = $this->config ['authorize_url'];
              $request = "";
              break;
              
          case "AccessToken" :
              $this->config ['host'] = $this->config ['site'] . '/oauth/';
              break;
               
          default :
              if (isset ( $api )) {
                  if ($api == "core") {
                      $api_stem = "api.xro";
                      $api_version = $this->signatures ['core_version'];
                  }
                  if ($api == "payroll") {
                      $api_stem = "payroll.xro";
                      $api_version = $this->signatures ['payroll_version'];
                  }
                  if ($api == "file") {
                      $api_stem = "files.xro";
                      $api_version = $this->signatures ['file_version'];
                  }
              }
              $this->config ['host'] = $this->config ['xero_url'] . $api_stem . '/' . $api_version . '/';
              break;
      }
      
      return implode ( array (
            $this->config ['host'],
            $request 
      ) );
   }   
   
   function request($method, $url, $params = array(), $xml = "", $format = 'xml') 
   {
      // removed these as function parameters for now
      
      $useauth = true;
      $multipart = false;
      $this->headers = array ();
		
      if (isset ( $format )) {
         switch ($format) {
            case "pdf" :
               $this->headers ['Accept'] = 'application/pdf';
               break;
            case "json" :
               $this->headers ['Accept'] = 'application/json';
               break;
            case "xml" :
            default :
               $this->headers ['Accept'] = 'application/xml';
               break;
         }
      }
		
      if (isset ( $params ['If-Modified-Since'] )) {
         $modDate = "If-Modified-Since: " . $params ['If-Modified-Since'];
         $this->headers ['If-Modified-Since'] = $params ['If-Modified-Since'];
      }
		
      if ($xml !== "") {
         $xml = trim($xml);
         $this->xml = $xml;
      }
		
      if ($method == "POST")
         $params ['xml'] = $xml;
		
      $this->prepare_method ( $method );
      $this->config ['multipart'] = $multipart;
      $this->url = $url;
      $oauthObject = new OAuthSimple ();
      try {
         $this->sign = $oauthObject->sign ( array (
               'path' => $url,
               'action' => $method,
               'parameters' => array_merge ( $params, array (
                     'oauth_signature_method' => $this->config ['signature_method'] 
               ) ),
               'signatures' => $this->config 
         ) );
         
         print_r($this->config);
         print_r($this->sign); 
      } 

      catch ( Exception $e ) {
         $errorMessage = 'XeroOAuth::request() ' . $e->getMessage ();
         $this->response['response'] = $errorMessage;
         $this->response['helper'] = $url;
         return $this->response;
      }
      $this->format = $format;
		
      $curlRequest = $this->curlit ();
		
      if ($this->response ['code'] == 401 && isset ( $this->config ['session_handle'] )) {
         if ((strpos ( $this->response ['response'], "oauth_problem=token_expired" ) !== false)) {
            $this->response ['helper'] = "TokenExpired";
         } else {
            $this->response ['helper'] = "TokenFatal";
         }
      }
      if ($this->response ['code'] == 403) {
         $errorMessage = "It looks like your Xero Entrust cert issued by Xero is either invalid or has expired. See http://developer.xero.com/api-overview/http-response-codes/#403 for more";
         // default IIS page isn't informative, a little swap
         $this->response ['response'] = $errorMessage;
         $this->response ['helper'] = "SetupIssue";
      }
      if ($this->response ['code'] == 0) {
         $errorMessage = "It looks like your Xero Entrust cert issued by Xero is either invalid or has expired. See http://developer.xero.com/api-overview/http-response-codes/#403 for more";
         $this->response ['response'] = $errorMessage;
         $this->response ['helper'] = "SetupIssue";
      }
		
      return $this->response;
   }   
   
   function init_param($args=array()) 
   {
       $this->_parameters['oauth_consumer_key']=$this->config['consumer_key'];

       $this->_parameters['oauth_token'] = $this->oauthSession['oauth_token'];

       $this->_parameters['oauth_timestamp'] = time();
   	 
       $this->_parameters['action'] = $args['action'];
   	 
       $this->_parameters['method'] = $this->config ['signature_method'];
       
       
       if (!empty($args['signatures']))
           $this->signatures($args['signatures']);
       if (empty($args['parameters']))
           $args['parameters']=array();        // squelch the warning.
       $this->setParameters($args['parameters']);
       $normParams = $this->_normalizedParameters();
       $this->_parameters['oauth_signature'] = $this->_generateSignature($normParams);
       return Array(
            'parameters' => $this->_parameters,
            'signature' => $this->_oauthEscape($this->_parameters['oauth_signature']),
            'signed_url' => $this->_path . '?' . $this->_normalizedParameters('true'),
            'header' => $this->getHeaderString(),
            'sbs'=> $this->sbs
           );
   }   
   
   function _generateSignature () 
   {
        $secretKey = '';
        if(isset($this->config['shared_secret']))
            $secretKey = $this->_oauthEscape($this->config['shared_secret']);
            $secretKey .= '&';
        if(isset($this->oauthSession['oauth_token_secret'])
            $secretKey .= $this->_oauthEscape($this->oauthSession['oauth_token_secret']);
            switch($this->config ['signature_method'])
            {
                case 'RSA-SHA1':

                    $publickey = "";
                    // Fetch the public key
                    if($publickey = openssl_get_publickey($this->_readFile($this->signatures['rsa_public_key']))){

                    }else{
                        throw new Exception('Cannot access public key for signing');
                    }
                
                    $privatekeyid = "";
                    // Fetch the private key
                    if($privatekeyid = openssl_pkey_get_private($this->_readFile($this->signatures['rsa_private_key']))) {                    
                        // Sign using the key
                        $this->sbs = $this->_oauthEscape($this->_action).'&'.$this->_oauthEscape($this->_path).'&'.$this->_oauthEscape($this->_normalizedParameters());

                        $ok = openssl_sign($this->sbs, $signature, $privatekeyid);

                        // Release the key resource
                        openssl_free_key($privatekeyid);

                        return base64_encode($signature);

                    }else{
                        throw new Exception('Cannot access private key for signing');
                    }


                case 'PLAINTEXT':
                    return urlencode($secretKey);

                case 'HMAC-SHA1':
                    $this->sbs = $this->_oauthEscape($this->_action).'&'.$this->_oauthEscape($this->_path).'&'.$this->_oauthEscape($this->_normalizedParameters());
                    //error_log('SBS: '.$sigString);
                    return base64_encode(hash_hmac('sha1',$this->sbs,$secretKey,true));

                default:
                    throw new Exception('Unknown signature method for OAuthSimple');
        }
   }

   function _getNonce($length=5) 
   {
        $result = '';
        $cLength = strlen($this->_nonce_chars);
        for ($i=0; $i < $length; $i++) {        
            $rnum = rand(0,$cLength);
            $result .= substr($this->_nonce_chars,$rnum,1);
        }
        $this->_parameters['oauth_nonce'] = $result;
        return $result;
   }   
   
   function _oauthEscape($string) 
   {
        if ($string === 0) {
            return 0;        
        }

        if (empty($string)) {
            return '';        
        }

        if (is_array($string)) {
            throw new Exception('Array passed to _oauthEscape');        
        }

        $string = rawurlencode($string);
        $string = str_replace('+','%20',$string);
        $string = str_replace('!','%21',$string);
        $string = str_replace('*','%2A',$string);
        $string = str_replace('\'','%27',$string);
        $string = str_replace('(','%28',$string);
        $string = str_replace(')','%29',$string);
        return $string;
   }   
      
   function _readFile($filePath) 
   {

        $fp = fopen($filePath,"r");

        $file_contents = fread($fp,8192);

        fclose($fp);

        return $file_contents;
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
       $response = $this->XeroOAuth->request('GET', $this->url('Invoices', 'core'), array('order' => 'Total DESC'));
       if ($this->XeroOAuth->response['code'] != 200) {
                       
           throw new Exception('Xero Error: ' . $this->XeroOAuth->response['response']);
           
           return;                
       }	
       
       $invoiceList = $this->XeroOAuth->parseResponse($this->XeroOAuth->response['response'], $this->XeroOAuth->response['format']);
       return $invoiceList;           
   }
   
   public function getInvoice($invoiceID)
   {
       if($invoiceID == '') {
           return;
       }
       $param = array();
       $response = $this->XeroOAuth->request('GET', $this->url('Invoices/' . $invoiceID , 'core'), $param ,'','json');
       
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
       
       $response = $this->XeroOAuth->request('GET', $this->url('Contacts' , 'core'), $param ,'','json');
       
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
   
   public function createInvoice($xml)
   {
      if($xml == '') {
           return;
      }
      
      $response = $this->XeroOAuth->request('POST', $this->url('Invoices', 'core'), array(), $xml,'json');
   	
      if ($this->XeroOAuth->response['code'] != 200) {
           // Error
        	   
           throw new Exception('Xero Error: ' . $this->XeroOAuth->response['response']);
           
           return;
                 
      }
            
      $invoice = $this->XeroOAuth->parseResponse($this->XeroOAuth->response['response'], $this->XeroOAuth->response['format']);
      
      return $invoice;           
     
   }
}

