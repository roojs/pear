<?php
/**
 * OAuth2 Google API authentication
 *
 */
class Services_GapiOAuth2
{
    const scope_url = 'https://www.googleapis.com/auth/analytics.readonly'; // fixme...
    const request_url = 'https://www.googleapis.com/oauth2/v3/token';
    const grant_type = 'urn:ietf:params:oauth:grant-type:jwt-bearer';
    const header_alg = 'RS256';
    const header_typ = 'JWT';

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
    public function fetchToken($client_email, $key_file, $delegate_email = null)
    {
        $header = array(
            "alg" => self::header_alg,
            "typ" => self::header_typ,
        );

        $claimset = array(
            "iss" => $client_email,
            "scope" => self::scope_url,
            "aud" => self::request_url,
            "exp" => time() + (60 * 60),
            "iat" => time(),
        );

        if(!empty($delegate_email)) {
            $claimset["sub"] = $delegate_email;
        }

        $data = $this->base64URLEncode(json_encode($header)) . '.' . $this->base64URLEncode(json_encode($claimset));

        if (!file_exists($key_file)) {
            if ( !file_exists(__DIR__ . DIRECTORY_SEPARATOR . $key_file) ) {
                throw new Exception('GAPI: Failed load key file "' . $key_file . '". File could not be found.');
            } else {
                $key_file = __DIR__ . DIRECTORY_SEPARATOR . $key_file;
            }
        }

        $key_data = file_get_contents($key_file);
        
        if (empty($key_data)) {
            throw new Exception('GAPI: Failed load key file "' . $key_file . '". File could not be opened or is empty.');
        }

        openssl_pkcs12_read($key_data, $certs, 'notasecret');

        if (!isset($certs['pkey'])) {
            throw new Exception('GAPI: Failed load key file "' . $key_file . '". Unable to load pkcs12 check if correct p12 format.');
        }

        openssl_sign($data, $signature, openssl_pkey_get_private($certs['pkey']), "sha256");

        $post_variables = array(
            'grant_type' => self::grant_type,
            'assertion' => $data . '.' . $this->base64URLEncode($signature),
        );

        $url = new Services_Gapi_Request(self::request_url);
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
