<?php

require_once 'HTTP/OAuth2/Encryption/EncryptionInterface.php';
require_once 'HTTP/OAuth2/Encryption/Jwt.php';
require_once 'HTTP/OAuth2/Storage/AccessTokenInterface.php';
require_once 'HTTP/OAuth2/Storage/RefreshTokenInterface.php';
require_once 'HTTP/OAuth2/Storage/PublicKeyInterface.php';
require_once 'HTTP/OAuth2/Storage/Memory.php';

/**
 *
 * @author Brent Shaffer <bshafs at gmail dot com>
 */
class HTTP_OAuth2_ResponseType_CryptoToken extends HTTP_OAuth2_ResponseType_AccessToken
{
    protected $publicKeyStorage;
    protected $encryptionUtil;

    /**
     * @param $config
     *  - store_encrypted_token_string (bool true)
     *       whether the entire encrypted string is stored,
     *       or just the token ID is stored
     */
    public function __construct(HTTP_OAuth2_Storage_PublicKeyInterface $publicKeyStorage = null, HTTP_OAuth2_Storage_AccessTokenInterface $tokenStorage = null, HTTP_OAuth2_Storage_RefreshTokenInterface $refreshStorage = null, array $config = array(), HTTP_OAuth2_Encryption_EncryptionInterface $encryptionUtil = null)
    {
        $this->publicKeyStorage = $publicKeyStorage;
        $config = array_merge(array(
            'store_encrypted_token_string' => true,
        ), $config);
        if (is_null($tokenStorage)) {
            // a pass-thru, so we can call the parent constructor
            $tokenStorage = new Memory();
        }
        if (is_null($encryptionUtil)) {
            $encryptionUtil = new Jwt();
        }
        $this->encryptionUtil = $encryptionUtil;
        parent::__construct($tokenStorage, $refreshStorage, $config);
    }

    /**
     * Handle the creation of access token, also issue refresh token if supported / desirable.
     *
     * @param $client_id
     * Client identifier related to the access token.
     * @param $user_id
     * User ID associated with the access token
     * @param $scope
     * (optional) Scopes to be stored in space-separated string.
     * @param bool $includeRefreshToken
     * If true, a new refresh_token will be added to the response
     *
     * @see http://tools.ietf.org/html/rfc6749#section-5
     * @ingroup oauth2_section_5
     */
    public function createAccessToken($client_id, $user_id, $scope = null, $includeRefreshToken = true)
    {
        // token to encrypt
        $expires = time() + $this->config['access_lifetime'];
        $cryptoToken = array(
            'id'         => $this->generateAccessToken(),
            'client_id'  => $client_id,
            'user_id'    => $user_id,
            'expires'    => $expires,
            'token_type' => $this->config['token_type'],
            'scope'      => $scope
        );

        /*
         * Issue a refresh token also, if we support them
         *
         * Refresh Tokens are considered supported if an instance of OAuth2\Storage\RefreshTokenInterface
         * is supplied in the constructor
         */
        if ($includeRefreshToken && $this->refreshStorage) {
            $cryptoToken["refresh_token"] = $this->generateRefreshToken();
            $expires = 0;
            if ($this->config['refresh_token_lifetime'] > 0) {
                $expires = time() + $this->config['refresh_token_lifetime'];
            }
            $this->refreshStorage->setRefreshToken($cryptoToken['refresh_token'], $client_id, $user_id, $expires, $scope);
        }

        /*
         * Encode the token data into a single access_token string
         */
        $access_token = $this->encodeToken($cryptoToken, $client_id);

        /*
         * Save the token to a secondary storage.  This is implemented on the
         * OAuth2\Storage\CryptoToken side, and will not actually store anything,
         * if no secondary storage has been supplied
         */
        $token_to_store = $this->config['store_encrypted_token_string'] ? $access_token : $cryptoToken['id'];
        $this->tokenStorage->setAccessToken($token_to_store, $client_id, $user_id, $this->config['access_lifetime'] ? time() + $this->config['access_lifetime'] : null, $scope);

        // token to return to the client
        $token = array(
            'access_token' => $access_token,
            'expires_in' => $this->config['access_lifetime'],
            'token_type' => $this->config['token_type'],
            'scope' => $scope
        );

        return $token;
    }

    protected function encodeToken(array $token, $client_id = null)
    {
        $private_key = $this->publicKeyStorage->getPrivateKey($client_id);
        $algorithm   = $this->publicKeyStorage->getEncryptionAlgorithm($client_id);

        return $this->encryptionUtil->encode($token, $private_key, $algorithm);
    }
}
