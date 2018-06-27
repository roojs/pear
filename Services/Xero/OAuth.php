<?php

require_once 'Services/Xero/OAuthSign.php';

/**
 * Define a custom Exception for easy trap and detection
 */
class XeroOAuthException extends Exception {
}

class Services_Xero_OAuth
{
    var $_xero_defaults =array (
        'xero_url' => 'https://api.xero.com/',
        'site' => 'https://api.xero.com',
        'authorize_url' => 'https://api.xero.com/oauth/Authorize',
        'signature_method' => 'RSA-SHA1'
    );
    
    
    var $_xero_consumer_options = array (
        'request_token_path' => 'oauth/RequestToken',
        'access_token_path' => 'oauth/AccessToken',
        'authorize_path' => 'oauth/Authorize',
    );
    
    var $_xero_curl_options = array ( // you probably don't want to change any of these curl values
        CURLOPT_CONNECTTIMEOUT      => 30,
        CURLOPT_TIMEOUT             => 20,
        // for security you may want to set this to TRUE. If you do you need
        // to install the servers certificate in your local certificate store.
        CURLOPT_SSL_VERIFYPEER       => 2,
        // include ca-bundle.crt from http://curl.haxx.se/ca/cacert.pem
        //'curl_cainfo' => $ca_cert_path . '/ca-bundle.crt',
        CURLOPT_SSL_VERIFYPEER       => '/etc/ssl/certs/ca-certificates.crt',
        CURLOPT_FOLLOWLOCATION      => false, // whether to follow redirects or not
                                        // TRUE/1 is not a valid ssl verifyhost value with curl >= 7.28.1 and 2 is more secure as well.
                                        // More details here: http://php.net/manual/en/function.curl-setopt.php
        CURLOPT_SSL_VERIFYHOST      => 2,
                            // support for proxy servers
        CURLOPT_PROXY => false, // really you don't want to use this if you are using streaming
        CURLOPT_PROXYUSERPWD    => false, // format username:password for proxy, if required
        CURLOPT_ENCODING        => '', // leave blank for all supported formats, else use gzip, deflate, identity
        CURLOPT_VERBOSE         => true ,
        CURLOPT_USERAGENT       => 'XeroOAuth-PHP',

      );
                
      
    var $_action;
    var $_nonce_chars;
	
   /**
    * Creates a new XeroOAuth object
    *
    * @param string $config,
    *        	the configuration settings
    */
    function __construct($config) 
    {    
        $this->params = array ();
        $this->headers = array ();
        $this->auto_fixed_time = false;
        $this->buffer = null;
        $this->request_params = array();
        
        foreach($this->_xero_defaults as $k => $v) {
            if (isset($config[$k])) {
                $this->_xero_defaults[$k] = $config[$k];
            }
        }
        
        if (!empty($config['application_type']) && ($config['application_type'] == "Public")) {
            $this->_xero_defaults['signature_method'] =  'HMAC-SHA1';
        }
        
        foreach($this->_xero_curl_options as $k=>$v) {
            if (isset($config[$k])) {
                $this->_xero_curl_options[$k] = $config[$k];
            }
        }
         
         
         
        $this->config =  $config ;
    }
	
   /**
    * Utility function to parse the returned curl headers and store them in the
    * class array variable.
    *
    * @param object $ch
    *        	curl handle
    * @param string $header
    *        	the response headers
    * @return the string length of the header
    */
   private function curlHeader($ch, $header) 
   {
      $i = strpos ( $header, ':' );
      if (! empty ( $i )) {
         $key = str_replace ( '-', '_', strtolower ( substr ( $header, 0, $i ) ) );
         $value = trim ( substr ( $header, $i + 2 ) );
         $this->response ['headers'] [$key] = $value;
      }
      return strlen ( $header );
   }
	
   /**
    * Utility function to parse the returned curl buffer and store them until
    * an EOL is found.
    * The buffer for curl is an undefined size so we need
    * to collect the content until an EOL is found.
    *
    * This function calls the previously defined streaming callback method.
    *
    * @param object $ch
    *        	curl handle
    * @param string $data
    *        	the current curl buffer
    */
   private function curlWrite($ch, $data) 
   {
      $l = strlen ( $data );
      if (strpos ( $data, $this->config ['streaming_eol'] ) === false) {
         $this->buffer .= $data;
         return $l;
      }
		
      $buffered = explode ( $this->config ['streaming_eol'], $data );
      $content = $this->buffer . $buffered [0];
		
      $this->metrics ['tweets'] ++;
      $this->metrics ['bytes'] += strlen ( $content );
		
      if (! function_exists ( $this->config ['streaming_callback'] ))
         return 0;
		
      $metrics = $this->update_metrics ();
      $stop = call_user_func ( $this->config ['streaming_callback'], $content, strlen ( $content ), $metrics );
      $this->buffer = $buffered [1];
      if ($stop)
         return 0;
		
      return $l;
   }
	
   /**
    * Extracts and decodes OAuth parameters from the passed string
    *
    * @param string $body
    *        	the response body from an OAuth flow method
    * @return array the response body safely decoded to an array of key => values
    */
   function extract_params($body) 
   {
      $kvs = explode ( '&', $body );
      $decoded = array ();
      foreach ( $kvs as $kv ) {
         $kv = explode ( '=', $kv, 2 );
         $kv [0] = $this->safe_decode ( $kv [0] );
         $kv [1] = $this->safe_decode ( $kv [1] );
         $decoded [$kv [0]] = $kv [1];
      }
      return $decoded;
   }
	
   /**
    * Encodes the string or array passed in a way compatible with OAuth.
    * If an array is passed each array value will will be encoded.
    *
    * @param mixed $data
    *        	the scalar or array to encode
    * @return $data encoded in a way compatible with OAuth
    */
   private function safe_encode($data) 
   {
      if (is_array ( $data )) {
         return array_map ( array (
               $this,
               'safe_encode' 
         ), $data );
      } else if (is_scalar ( $data )) {
         return str_ireplace ( array (
               '+',
               '%7E' 
         ), array (
               ' ',
               '~' 
         ), rawurlencode ( $data ) );
      } else {
         return '';
      }
   }
	
   /**
    * Decodes the string or array from it's URL encoded form
    * If an array is passed each array value will will be decoded.
    *
    * @param mixed $data
    *        	the scalar or array to decode
    * @return $data decoded from the URL encoded form
    */
   private function safe_decode($data) 
   {
      if (is_array ( $data )) {
         return array_map ( array (
               $this,
               'safe_decode' 
         ), $data );
      } else if (is_scalar ( $data )) {
         return rawurldecode ( $data );
      } else {
         return '';
      }
   }
	
   /**
    * Prepares the HTTP method for use in the base string by converting it to
    * uppercase.
    *
    * @param string $method
    *        	an HTTP method such as GET or POST
    * @return void value is stored to a class variable
    * @author themattharris
    */
   private function prepare_method($method) 
   {
      $this->method = strtoupper ( $method );
   }
	
   /**
    * Makes a curl request.
    * Takes no parameters as all should have been prepared
    * by the request method
    *
    * @return void response data is stored in the class variable 'response'
    */
   private function curlit() 
   {
      $this->request_params = array();
	
		
      // configure curl
        $c = curl_init ();
         
        curl_setopt_array ( $c, $this->_xero_curl_options);
        curl_setopt_array ( $c, array (
                         
            CURLOPT_RETURNTRANSFER      => TRUE,
            CURLOPT_URL                 => $this->sign ['signed_url'],
            
            // process the headers
            CURLOPT_HEADERFUNCTION => array (   $this, 'curlHeader'   ),
            CURLOPT_HEADER => FALSE,
            CURLINFO_HEADER_OUT => TRUE 
        ) );
          
        /* ENTRUST CERTIFICATE DEPRECATED
        if ($this->config ['application_type'] == "Partner") {
           curl_setopt_array ( $c, array (
                 // ssl client cert options for partner apps
                 CURLOPT_SSLCERT => $this->config ['curl_ssl_cert'],
                 CURLOPT_SSLKEYPASSWD => $this->config ['curl_ssl_password'],
                 CURLOPT_SSLKEY => $this->config ['curl_ssl_key'] 
           ) );
        }
        */
          
        if (isset ( $this->config ['is_streaming'] )) {
           // process the body
           $this->response ['content-length'] = 0;
           curl_setopt ( $c, CURLOPT_TIMEOUT, 0 );
           curl_setopt ( $c, CURLOPT_WRITEFUNCTION, array (
                 $this,
                 'curlWrite' 
           ) );
        }
        
          
        switch ($this->method) {
            case 'GET' :
                $contentLength = 0;
                break;
            
            case 'POST' :
                curl_setopt ( $c, CURLOPT_POST, TRUE );
                $post_body = $this->safe_encode ( $this->xml );
                curl_setopt ( $c, CURLOPT_POSTFIELDS, $post_body );
                $this->request_params ['xml'] = $post_body;
                $contentLength = strlen ( $post_body );
                $this->headers ['Content-Type'] = 'application/x-www-form-urlencoded';
                    
               break;
            
            case 'PUT' :
               $fh = tmpfile();
               if ($this->format == "file") {
                  $put_body = $this->xml;
               } else {
                  $put_body = $this->safe_encode ( $this->xml );
                  $this->headers ['Content-Type'] = 'application/x-www-form-urlencoded';
               }
               fwrite ( $fh, $put_body );
               rewind ( $fh );
               curl_setopt ( $c, CURLOPT_PUT, true );
               curl_setopt ( $c, CURLOPT_INFILE, $fh );
               curl_setopt ( $c, CURLOPT_INFILESIZE, strlen ( $put_body ) );
               $contentLength = strlen ( $put_body );
                   
               break;
            
            
            default :
               curl_setopt ( $c, CURLOPT_CUSTOMREQUEST, $this->method );
        } 
		
        if (! empty ( $this->request_params )) {
           // if not doing multipart we need to implode the parameters
           if (! $this->config ['multipart']) {
              foreach ( $this->request_params as $k => $v ) {
                 $ps [] = "{$k}={$v}";
              }
              $this->request_payload = implode ( '&', $ps );
           }
           curl_setopt ( $c, CURLOPT_POSTFIELDS, $this->request_payload);
            
        } else {
           // CURL will set length to -1 when there is no data
           $this->headers ['Content-Length'] = $contentLength;
        }
		
        $this->headers ['Expect'] = '';
		
        if (! empty ( $this->headers )) {
            foreach ( $this->headers as $k => $v ) {
               $headers [] = trim ( $k . ': ' . $v );
            }
            curl_setopt ( $c, CURLOPT_HTTPHEADER, $headers );
        }
           
        if (isset ( $this->config ['prevent_request'] ) && false == $this->config ['prevent_request']) {
            return;
        }
			
         // do it!
      $response = curl_exec ( $c );
      if ($response === false) {
         $response = 'Curl error: ' . curl_error ( $c );
         $code = 1;
      } else {
         $code = curl_getinfo ( $c, CURLINFO_HTTP_CODE );
      }
		
      $info = curl_getinfo ( $c );
		
      curl_close ( $c );
      if (isset ( $fh )) {
         fclose( $fh );
      }
		
      // store the response
      $this->response ['code'] = $code;
      $this->response ['response'] = $response;
      $this->response ['info'] = $info;
      $this->response ['format'] = $this->format;
      
      return $code;
   }
	
   /**
    * Make an HTTP request using this library.
    * This method doesn't return anything.
    * Instead the response should be inspected directly.
    *
    * @param string $method
    *        	the HTTP method being used. e.g. POST, GET, HEAD etc
    * @param string $url
    *        	the request URL without query string parameters
    * @param array $params
    *        	the request parameters as an array of key=value pairs
    * @param string $format
    *        	the format of the response. Default json. Set to an empty string to exclude the format
    *        	
    */
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
          
        if ($method == "POST") {
            $params ['xml'] = $xml;
        }
        $this->prepare_method ( $method );
        $this->config ['multipart'] = $multipart;
        $this->url = $url;
        $oauthObject = new Services_Xero_OAuthSign ();
        try {
           $this->sign = $oauthObject->sign ( array (
                 'path' => $url,
                 'action' => $method,
                 'parameters' => array_merge ( $params, array (
                       'oauth_signature_method' => $this->_xero_defaults['signature_method'] 
                 ) ),
                 'signatures' => $this->config 
           ) );
            
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
        
         $this->response['result']  = false;
        if ($this->response['code'] == 200 && !empty($this->response['format'])) {
            $this->response['result'] = $this->parseResponse($this->response['response'], $this->response['format']);
        }
        
        return $this->response;
    }
	
   /**
    * Convert the response into usable data
    *
    * @param string $response
    *        	the raw response from the API
    * @param string $format
    *        	the format of the response
    * @return string the concatenation of the host, API version and API method
    */
   function parseResponse($response, $format) 
   {
      if (isset ( $format )) {
         switch ($format) {
            case "pdf" :
               $theResponse = $response;
               break;
            case "json" :
               $theResponse = json_decode ( $response );
               break;
            default :
               $theResponse = simplexml_load_string ( $response );
               break;
         }
      }
      return $theResponse;
   }
	
   /**
    * Utility function to create the request URL in the requested format
    *
    * @param string $request
    *        	the API method without extension
    * @return string the concatenation of the host, API version and API method
    */
   function url($request, $api = "core") 
   {
      if ($request == "RequestToken") {
         $this->config ['host'] = $this->_xero_defaults  ['site'] . '/oauth/';
      } elseif ($request == "Authorize") {
         $this->config ['host'] = $this->_xero_defaults  ['authorize_url'];
         $request = "";
      } elseif ($request == "AccessToken") {
         $this->config ['host'] = $this->_xero_defaults  ['site'] . '/oauth/';
      } else {
         if (isset ( $api )) {
            if ($api == "core") {
               $api_stem = "api.xro";
               $api_version = $this->config ['core_version'];
            }
            if ($api == "payroll") {
               $api_stem = "payroll.xro";
               $api_version = $this->config ['payroll_version'];
            }
            if ($api == "file") {
               $api_stem = "files.xro";
               $api_version = $this->config ['file_version'];
            }
         }
         $this->config ['host'] = $this->_xero_defaults  ['xero_url'] . $api_stem . '/' . $api_version . '/';
      }
		
      return implode ( array (
            $this->config ['host'],
            $request 
      ) );
   }
	
   /**
    * Refreshes the access token for partner API type applications
    *
    * @param string $accessToken
    *        	the current access token for the session
    * @param string $sessionHandle
    *        	the current session handle for the session
    * @return array response array from request
    */
   function refreshToken($accessToken, $sessionHandle) 
   {
      $code = $this->request ( 'GET', $this->url ( 'AccessToken', '' ), array (
            'oauth_token' => $accessToken,
            'oauth_session_handle' => $sessionHandle 
      ) );
      if ($this->response ['code'] == 200) {
			
         $response = $this->extract_params ( $this->response ['response'] );
			
         return $response;
      } else {
         $this->response ['helper'] = "TokenFatal";
         return $this->response;
      }
   }
	
   /*
    * Run some basic checks on our config options etc to make sure all is ok
    * Oddly - this is run from the caller???
    * 
    */
    
    function diagnostics() 
    {
        $testOutput = array ();
  
          
        if (empty($this->config ['application_type']) || $this->config ['application_type'] == 'Partner' || $this->config ['application_type'] == 'Private') {
              
            if (! file_exists ( $this->config ['rsa_public_key'] )) {
                $testOutput ['rsa_cert_error'] =
                    "Can't read the self-signed SSL cert. Private and Partner API applications require a self-signed X509 cert ".
                    "http://developer.xero.com/documentation/advanced-docs/public-private-keypair/ \n";
            }
            
            if (file_exists ( $this->config ['rsa_public_key'] )) {
                $data = openssl_x509_parse ( file_get_contents ( $this->config ['rsa_public_key'] ) );
                $validFrom = date ( 'Y-m-d H:i:s', $data ['validFrom_time_t'] );
                if (time () < $data ['validFrom_time_t']) {
                    $testOutput ['ssl_cert_error'] = "Application cert not yet valid - cert valid from " . $validFrom . "\n";
                }
                $validTo = date ( 'Y-m-d H:i:s', $data ['validTo_time_t'] );
                if (time () > $data ['validTo_time_t']) {
                    $testOutput ['ssl_cert_error'] = "Application cert cert expired - cert valid to " . $validFrom . "\n";
                }
            }
            if (! file_exists ( $this->config ['rsa_private_key'] )) {
                $testOutput ['rsa_cert_error'] =
                    "Can't read the self-signed cert key. Check your rsa_private_key config variable. Private and Partner API applications require a self-signed X509 cert " .
                    "http://developer.xero.com/documentation/advanced-docs/public-private-keypair/ \n";
            }
            
            if (file_exists ( $this->config ['rsa_private_key'] )) {
                $cert_content = file_get_contents ( $this->config ['rsa_public_key'] );
                $priv_key_content = file_get_contents ( $this->config ['rsa_private_key'] );
                if (! openssl_x509_check_private_key ( $cert_content, $priv_key_content )) {
                     $testOutput ['rsa_cert_error'] = "Application certificate and key do not match \n";
                }
               
            }
        }
          
        return $testOutput;
    }
    
}
