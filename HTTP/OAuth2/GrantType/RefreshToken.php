<?php

require_once 'HTTP/OAuth2/Storage/RefreshTokenInterface.php';
require_once 'HTTP/OAuth2/ResponseType/AccessTokenInterface.php';
require_once 'HTTP/OAuth2/RequestInterface.php';
require_once 'HTTP/OAuth2/ResponseInterface.php';

/**
 *
 * @author Brent Shaffer <bshafs at gmail dot com>
 */
class HTTP_OAuth2_GrantType_RefreshToken implements HTTP_OAuth2_GrantType_GrantTypeInterface
{
    private $refreshToken;

    protected $storage;
    protected $config;

    /**
     * @param OAuth2\Storage\RefreshTokenInterface $storage
     * REQUIRED Storage class for retrieving refresh token information
     * @param array $config
     * OPTIONAL Configuration options for the server
     * @code
     * $config = array(
     *   'always_issue_new_refresh_token' => true, // whether to issue a new refresh token upon successful token request
     * );
     * @endcode
     */
    public function __construct(HTTP_OAuth2_Storage_RefreshTokenInterface $storage, $config = array())
    {
        $this->config = array_merge(array(
            'always_issue_new_refresh_token' => false
        ), $config);
        $this->storage = $storage;
    }

    public function getQuerystringIdentifier()
    {
        return 'refresh_token';
    }

    public function validateRequest(HTTP_OAuth2_RequestInterface $request, HTTP_OAuth2_ResponseInterface $response)
    {
        if (!$request->request("refresh_token")) {
            $response->setError(400, 'invalid_request', 'Missing parameter: "refresh_token" is required');

            return null;
        }

        if (!$refreshToken = $this->storage->getRefreshToken($request->request("refresh_token"))) {
            $response->setError(400, 'invalid_grant', 'Invalid refresh token');

            return null;
        }

        if ($refreshToken['expires'] > 0 && $refreshToken["expires"] < time()) {
            $response->setError(400, 'invalid_grant', 'Refresh token has expired');

            return null;
        }

        // store the refresh token locally so we can delete it when a new refresh token is generated
        $this->refreshToken = $refreshToken;

        return true;
    }

    public function getClientId()
    {
        return $this->refreshToken['client_id'];
    }

    public function getUserId()
    {
        return isset($this->refreshToken['user_id']) ? $this->refreshToken['user_id'] : null;
    }

    public function getScope()
    {
        return $this->refreshToken['scope'];
    }

    public function createAccessToken(HTTP_OAuth2_ResponseType_AccessTokenInterface $accessToken, $client_id, $user_id, $scope)
    {
        /*
         * It is optional to force a new refresh token when a refresh token is used.
         * However, if a new refresh token is issued, the old one MUST be expired
         * @see http://tools.ietf.org/html/rfc6749#section-6
         */
        $issueNewRefreshToken = $this->config['always_issue_new_refresh_token'];
        $token = $accessToken->createAccessToken($client_id, $user_id, $scope, $issueNewRefreshToken);

        if ($issueNewRefreshToken) {
            $this->storage->unsetRefreshToken($this->refreshToken['refresh_token']);
        }

        return $token;
    }
}
