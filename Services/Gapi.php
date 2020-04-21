<?php

class Services_Gapi
{
  
    const DEV_MODE = false;
  
    var $api = 'sheets.googleapis.com';
    var $auth = null;
    var $account_entries = array();
    var $report_aggregate_metrics = array();
    var $report_root_parameters = array();
    var $results = array();
  
    /**
     * Constructor function for new gapi instances
     *
     * @param string $client_email Email of OAuth2 service account (XXXXX@developer.gserviceaccount.com)
     * @param string $key_file Location/filename of .p12 key file
     * @param string $delegate_email Optional email of account to impersonate
     * @return gapi
     */
    function __construct($client_email, $key_file, $api)
    {
        require_once 'Services/Gapi/OAuth2.php';
        $this->auth = new Services_Gapi_OAuth2();
        $this->auth->fetchToken($client_email, $key_file, null);
        switch ($api) {
            case 'sheets':  $this->url = 'http://sheets.google.com/v4/spreadsheets/'; break;
            case 'sheets':  $this->url = 'http://sheets.google.com/v4/spreadsheets/'; break;
            default:
                throw new Exception("Invalid API");
        }
    }
    
    function request($endpoint, $args = array())
    {
        require_once 'Services/Gapi/Request.php';
        $req = new Services_Gapi_Request($endpoint);
        $res = $url->get($args, $this->auth->generateAuthHeader());

        
    }
    
}