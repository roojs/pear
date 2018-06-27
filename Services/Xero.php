<?php

/*
 * Xero Library
 * https://developer.xero.com/documentation/libraries/php
 * https://github.com/XeroAPI/XeroOAuth-PHP
 * 
 */

require_once 'Services/Xero/OAuth.php';

class Services_Xero
{
    var $XeroOAuth;
    
    function __construct($config)
    {
        $this->XeroOAuth = new Services_Xero_OAuth($config);
    }
    
    function getItems($params = array())
    {
        $response = $XeroOAuth->request('GET', $XeroOAuth->url('Items', 'core'), array());
        if ($XeroOAuth->response['code'] == 200) {
            $items = $XeroOAuth->parseResponse($XeroOAuth->response['response'], $XeroOAuth->response['format']);
            echo "There are " . count($items->Items[0]). " items in this Xero organisation, the first one is: </br>";
            pr($items->Items[0]->Item);
        } else {
            outputError($XeroOAuth);
        }
        
        $response = $this->XeroOAuth->request('GET', $this->XeroOAuth->url('Items', 'core'), $params);
        
        if (empty($this->XeroOAuth['code']) || $this->XeroOAuth['code'] != 200) {
            throw new Exception('Xero Error: ' . $response['response']);
            return;
        }
        
        $items = $this->XeroOAuth->parseResponse($this->XeroOAuth->response['response'], $this->XeroOAuth->response['format']);
        
        

        $invoiceList = $this->XeroOAuth->parseResponse($response['response'], $response['format']);
        return $invoiceList;
    }
   
//    function connectXero()
//    {
//        $this->initialCheck = $this->XeroOAuth->diagnostics();
//        $this->checkErrors = count($this->initialCheck);
//        
//        if ($this->checkErrors > 0) {
//            return false;	        
//        }
//        
//        $this->session = $this->persistSession(array(
//            'oauth_token' => $this->XeroOAuth->config ['consumer_key'],
//            'oauth_token_secret' => $this->XeroOAuth->config ['shared_secret'],
//            'oauth_session_handle' => '' 
//        ) );
//             
//        $this->oauthSession = $this->retrieveSession();
//        
//        if (isset($this->oauthSession ['oauth_token'])){
//            $this->XeroOAuth->config ['access_token'] = $this->oauthSession ['oauth_token'];
//            $this->XeroOAuth->config ['access_token_secret'] = $this->oauthSession ['oauth_token_secret'];
//        }
//       
//    }
//    
//    function refreshToken()
//    {
//        $response = $this->XeroOAuth->refreshToken($this->oauthSession['oauth_token'], $this->oauthSession['oauth_session_handle']);
//
//        if ($response['code'] != 200) {
//            if ($response['helper'] == "TokenExpired") {
//                $this->XeroOAuth->refreshToken($this->oauthSession['oauth_token'], $this->oauthSession['session_handle']);
//            }
//
//            return false;
//        }
//
//        $this->session = $this->persistSession($response);
//        $this->oauthSession = $this->retrieveSession();
//
//        return true;
//    }
//    /**
//     * Persist the OAuth access token and session handle somewhere
//     * In my example I am just using the session, but in real world, this is should be a storage engine
//     *
//     * @param array $params the response parameters as an array of key=value pairs
//     */
//    function persistSession($response) 
//    {
//        if (isset($response)) {
//            $_SESSION[__CLASS__]['access_token'] = $response['oauth_token'];
//
//            $_SESSION[__CLASS__]['oauth_token_secret'] = $response['oauth_token_secret'];
//
//            if (isset($response['oauth_session_handle'])) {
//                $_SESSION[__CLASS__]['session_handle'] = $response['oauth_session_handle'];
//            }
//        } else {
//            return false;
//        }
//    }
//
//    /**
//    * Retrieve the OAuth access token and session handle
//    * In my example I am just using the session, but in real world, this is should be a storage engine
//    *
//    */
//    function retrieveSession()
//    {
//        if (isset($_SESSION[__CLASS__]['access_token'])) {
//            $response['oauth_token'] = $_SESSION[__CLASS__]['access_token'];
//
//            $response['oauth_token_secret'] = $_SESSION[__CLASS__]['oauth_token_secret'];
//
//            $response['oauth_session_handle'] = $_SESSION[__CLASS__]['session_handle'];
//
//            return $response;
//        }
//
//        return false;
//    }   
//   
//    public function getInvoiceList()
//    {
//        $response = $this->XeroOAuth->request('GET', $this->XeroOAuth->url('Invoices', 'core'), array('order' => 'Total DESC'));
//        if (empty($response['code']) || $response['code'] != 200) {
//            throw new Exception('Xero Error: ' . $response['response']);
//
//            return;
//        }
//
//        $invoiceList = $this->XeroOAuth->parseResponse($response['response'], $response['format']);
//        return $invoiceList;
//    }
//    
//    public function getInvoicesByFilter($param)
//    {
//        if (empty($param)) {
//            throw new Exception("Xero Error: invalid arguments to getInvoicesByFilter");
//        }
//
//        $response = $this->XeroOAuth->request('GET', $this->XeroOAuth->url('Invoices', 'core'), $param, '', 'json');
//
//        if (empty($response['code']) || $response['code'] != 200) {
//
//            throw new Exception('Xero Error: ' . $response['response']);
//
//            //outputError($XeroOAuth);
//        }
//
//        $result = $response['result'];
//
//        if (!$result || !$response['result'] || empty($response['result']->Invoices)) {
//            return false;
//        }
//
//        return $result->Invoices;
//    }   
//   
//    public function getInvoice($invoiceID)
//    {
//        if($invoiceID == '') {
//            return;
//        }
//        $param = array();
//        $response = $this->XeroOAuth->request('GET', $this->XeroOAuth->url('Invoices/' . $invoiceID , 'core'), $param ,'','json');
//        
//        if (empty($response['code']) ||  $response['code'] != 200) {
//          
//            throw new Exception('Xero Error: ' . $response['response']);
//                  
//            return;      
//           //outputError($XeroOAuth);
//        }
//        
//        $result = $this->XeroOAuth->parseResponse($response['response'], $response['format']);
//        
//        if(    !$result->Invoices 
//            || !count($result->Invoices[0]) 
//            || !$result->Invoices[0]->InvoiceNumber
//           ) {          	
//           return;                        
//        }
//         
//        return $result->Invoices[0];
//    }
//   
//    public function getContact($name)
//    {
//        if($name == '') {
//            return;
//        }
//        
//        $param = array('Where' => 'Name="'. $name. '"');
//        
//        $response = $this->XeroOAuth->request('GET', $this->XeroOAuth->url('Contacts' , 'core'), $param ,'','json');
//         
//        if (empty($response['code']) ||  $response['code'] != 200) {     
//            throw new Exception('Xero Error: ' . $response['response']);
//        }
//        
//        $contact = $response['result'];
//        
//        if (!$contact || empty($contact->Contacts[0])) {
//            throw new Exception('Could not find contact: ' . $response['response']);
//        }
//        
//        
//        return $contact->Contacts[0];
//    }
//    
//    public function createInvoice($inv)
//    {
//        if($inv == '') {
//             return;
//        }
//        
//        $response = $this->XeroOAuth->request('POST', $this->XeroOAuth->url('Invoices', 'core'), array(), $inv->toXMLString(),'json');
//      
//        if (empty($response['code']) ||  $response['code'] != 200) {
//             throw new Exception('Xero Error: ' . $response['response']);     
//        }
//              
//        $result = $response['result'];
//        
//        if(  !$result ||
//             !$result->Invoices  || 
//             !count($result->Invoices[0]) || 
//             !$result->Invoices[0]->InvoiceNumber
//            ) {          	
//            return;                        
//        }
//          
//        return $result->Invoices[0];           
//       
//    }
//    /**
//     * get the branding themes (normally templates of invoices etc..)
//     *
//     * without an arguemnt it will get all the branding themes,
//     * otherwise it will try and match
//     * eg. getBrandingThemes( [ "Name" => "Currency Conversion"] )
//     *
//     */
//    function getBrandingThemes($match = array())
//    {
//        $response = $this->XeroOAuth->request('GET',
//                $this->XeroOAuth->url('BrandingThemes', 'core'), array(), '','json');
//        
//        if (empty($response['code']) ||  $response['code'] != 200) {
//            throw new Exception('Xero Error: ' . $response['response']);     
//        }
//        if (empty($match)) {
//            return  $response['result']->BrandingThemes;
//        }
//        // not sure if this is an array when only one theme
//        foreach ($response['result']->BrandingThemes as $th) {
//            foreach($match as $k=>$v) {
//                if ($th->{$k} == $v) {
//                    return $th;
//                }
//            }
//            
//        }
//    }
}

