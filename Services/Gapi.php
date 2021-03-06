<?php
/*
 * notes :
 *
 * endpoints : https://sheets.googleapis.com/$discovery/rest?version=v4
 *
 */
class Services_Gapi
{
  
    
    var $url = false;
    var $auth = false;
  
  
    /**
     * Constructor function for new gapi instances
     *
      * @param string $json_file Downloaded from google..
     * @param string $delegate_email Optional email of account to impersonate
     * @return gapi
     */
    function __construct($json_file, $api)
    {
        require_once 'Services/Gapi/OAuth2.php';
        $this->auth = new Services_Gapi_OAuth2();
        $this->auth->fetchToken($json_file, null);
        // this must be discoverable?
        switch ($api) {
            case 'docs':  $this->url = 'https://docs.googleapis.com/v1/documents/'; break;
            case 'sheets':  $this->url = 'https://sheets.googleapis.com/v4/spreadsheets/'; break;
            default:
                throw new Exception("Invalid API");
        }
    }
    /**
     * @param string endpoint  = eg. {spreadsheetId}/values:batchGet
     * 
     */
    function get($endpoint, $args = array())
    {
        require_once 'Services/Gapi/Request.php';
        $req = new Services_Gapi_Request($this->url . $endpoint);
        return  $req->get($args, $this->auth->generateAuthHeader());
    }
    /**
     * @param string endpoint  = eg. {spreadsheetId}/values:batchGet
     * 
     */
    function post($endpoint, $get = array(), $post = array())
    {
        require_once 'Services/Gapi/Request.php';
        $req = new Services_Gapi_Request($this->url . $endpoint);
        return $req->post($get, $post, $this->auth->generateAuthHeader());
    }
}