<?php

class Services_Gapi
{
  
  const DEV_MODE = false;

  private $auth_method = null;
  private $account_entries = array();
  private $report_aggregate_metrics = array();
  private $report_root_parameters = array();
  private $results = array();

  /**
   * Constructor function for new gapi instances
   *
   * @param string $client_email Email of OAuth2 service account (XXXXX@developer.gserviceaccount.com)
   * @param string $key_file Location/filename of .p12 key file
   * @param string $delegate_email Optional email of account to impersonate
   * @return gapi
   */
  public function __construct($client_email, $key_file, $delegate_email = null) {
    if (version_compare(PHP_VERSION, '5.3.0') < 0) {
      throw new Exception('GAPI: PHP version ' . PHP_VERSION . ' is below minimum required 5.3.0.');
    }
    $this->auth_method = new gapiOAuth2();
    $this->auth_method->fetchToken($client_email, $key_file, $delegate_email);
  }
