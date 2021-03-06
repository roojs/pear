<?php

require_once 'HTTP/OAuth2/ClientAssertionType/HttpBasic.php';
require_once 'HTTP/OAuth2/ResponseType/AccessTokenInterface.php';
require_once 'HTTP/OAuth2/Storage/ClientCredentialsInterface.php';

/**
 * @author Brent Shaffer <bshafs at gmail dot com>
 *
 * @see OAuth2\ClientAssertionType_HttpBasic
 */
class HTTP_OAuth2_GrantType_ClientCredentials extends HTTP_OAuth2_ClientAssertionType_HttpBasic implements HTTP_OAuth2_GrantType_GrantTypeInterface
{
    private $clientData;

    public function __construct(HTTP_OAuth2_Storage_ClientCredentialsInterface $storage, array $config = array())
    {
        /**
         * The client credentials grant type MUST only be used by confidential clients
         *
         * @see http://tools.ietf.org/html/rfc6749#section-4.4
         */
        $config['allow_public_clients'] = false;

        parent::__construct($storage, $config);
    }

    public function getQuerystringIdentifier()
    {
        return 'client_credentials';
    }

    public function getScope()
    {
        $this->loadClientData();

        return isset($this->clientData['scope']) ? $this->clientData['scope'] : null;
    }

    public function getUserId()
    {
        $this->loadClientData();

        return isset($this->clientData['user_id']) ? $this->clientData['user_id'] : null;
    }

    public function createAccessToken(HTTP_OAuth2_ResponseType_AccessTokenInterface $accessToken, $client_id, $user_id, $scope)
    {
        /**
         * Client Credentials Grant does NOT include a refresh token
         *
         * @see http://tools.ietf.org/html/rfc6749#section-4.4.3
         */
        $includeRefreshToken = false;

        return $accessToken->createAccessToken($client_id, $user_id, $scope, $includeRefreshToken);
    }

    private function loadClientData()
    {
        if (!$this->clientData) {
            $this->clientData = $this->storage->getClientDetails($this->getClientId());
        }
    }
}
