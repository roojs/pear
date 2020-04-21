<?php

class Services_Gapi
{
  
    const DEV_MODE = false;
  
    var  $auth_method = null;
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
    public function __construct($client_email, $key_file, $delegate_email = null)
    {
        require_once 'Gapi/OAuth2.php';
        $this->auth_method = new Services_Gapi_OAuth2();
        $this->auth_method->fetchToken($client_email, $key_file, $delegate_email);
    }
