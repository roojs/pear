<?php
/**
 * OAuth2 Google API authentication
 *
 */
class Services_Gapi_OAuth2
{
      
    private function base64URLEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64URLDecode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    } 

    /**
     * Authenticate Google Account with OAuth2
     *
     * @param String $client_email
     * @param String $key_file
     * @param String $delegate_email
     * @return String Authentication token
     */
    public function fetchToken($json_file)
    {
        
        $cfg = is_array($json_file) ? (object)$json_file  : json_decode(file_get_contents($json_file));
        
        $header = array(
            "alg" => "RS256",
            "typ" => "JWT",
        );

        $claimset = array(
            "iss" => $cfg->client_email,
            "scope" => 'https://www.googleapis.com/auth/spreadsheets', // this should be derived somewhere..
            "aud" => $cfg->token_uri,
            "exp" => time() + (60 * 60),
            "iat" => time(),
        );

       
        $data = $this->base64URLEncode(json_encode($header)) . '.' . $this->base64URLEncode(json_encode($claimset));

    
         openssl_sign(
            $data,
            $signature, // returned
            $cfg->private_key,
            "sha256" // algo
        );
       
      
        $post_variables = array(
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $data . '.' . $this->base64URLEncode($signature),
        );
        require_once 'Services/Gapi/Request.php';
        $url = new Services_Gapi_Request($cfg->token_uri);
        $response = $url->post(null, $post_variables);
        $auth_token = json_decode($response['body'], true);

        if (substr($response['code'], 0, 1) != '2' || !is_array($auth_token) || empty($auth_token['access_token'])) {
            throw new Exception('GAPI: Failed to authenticate user. Error: "' . strip_tags($response['body']) . '"');
        }

        $this->auth_token = $auth_token['access_token'];

        return $this->auth_token;
    }

    /**
     * Return the auth token string retrieved from Google
     *
     * @return String
     */
    public function getToken()
    {
        return $this->auth_token;
    }
    
    /**
     * Generate authorization token header for all requests
     *
     * @param String $token
     * @return Array
     */
    public function generateAuthHeader($token=null)
    {
        if ($token == null) {
            $token = $this->auth_token;
        }
        return array('Authorization' => 'Bearer ' . $token);
    }
}
