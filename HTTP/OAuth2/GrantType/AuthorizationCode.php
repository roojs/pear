<?php

require_once 'HTTP/OAuth2/Storage/AuthorizationCodeInterface.php';
require_once 'HTTP/OAuth2/ResponseType/AccessTokenInterface.php';
require_once 'HTTP/OAuth2/GrantType/GrantTypeInterface.php';
require_once 'HTTP/OAuth2/RequestInterface.php';
require_once 'HTTP/OAuth2/ResponseInterface.php';

/**
 *
 * @author Brent Shaffer <bshafs at gmail dot com>
 */
class HTTP_OAuth2_GrantType_AuthorizationCode implements HTTP_OAuth2_GrantType_GrantTypeInterface
{
    protected $storage;
    protected $authCode;

    /**
     * @param OAuth2\Storage\AuthorizationCodeInterface $storage
     * REQUIRED Storage class for retrieving authorization code information
     */
    public function __construct(HTTP_OAuth2_Storage_AuthorizationCodeInterface $storage)
    {
        $this->storage = $storage;
    }

    public function getQuerystringIdentifier()
    {
        return 'authorization_code';
    }

    public function validateRequest(HTTP_OAuth2_RequestInterface $request, HTTP_OAuth2_ResponseInterface $response)
    {
        if (!$request->request('code')) {
            $response->setError(400, 'invalid_request', 'Missing parameter: "code" is required');

            return false;
        }

        $code = $request->request('code');
        if (!$authCode = $this->storage->getAuthorizationCode($code)) {
            $response->setError(400, 'invalid_grant', 'Authorization code doesn\'t exist or is invalid for the client');

            return false;
        }

        /*
         * 4.1.3 - ensure that the "redirect_uri" parameter is present if the "redirect_uri" parameter was included in the initial authorization request
         * @uri - http://tools.ietf.org/html/rfc6749#section-4.1.3
         */
        if (isset($authCode['redirect_uri']) && $authCode['redirect_uri']) {
            if (!$request->request('redirect_uri') || urldecode($request->request('redirect_uri')) != $authCode['redirect_uri']) {
                $response->setError(400, 'redirect_uri_mismatch', "The redirect URI is missing or do not match", "#section-4.1.3");

                return false;
            }
        }

        if (!isset($authCode['expires'])) {
            throw new Exception('Storage must return authcode with a value for "expires"');
        }

        if ($authCode["expires"] < time()) {
            $response->setError(400, 'invalid_grant', "The authorization code has expired");

            return false;
        }

        if (!isset($authCode['code'])) {
            $authCode['code'] = $code; // used to expire the code after the access token is granted
        }

        $this->authCode = $authCode;

        return true;
    }

    public function getClientId()
    {
        return $this->authCode['client_id'];
    }

    public function getScope()
    {
        return isset($this->authCode['scope']) ? $this->authCode['scope'] : null;
    }

    public function getUserId()
    {
        return isset($this->authCode['user_id']) ? $this->authCode['user_id'] : null;
    }

    public function createAccessToken(HTTP_OAuth2_ResponseType_AccessTokenInterface $accessToken, $client_id, $user_id, $scope)
    {
        $token = $accessToken->createAccessToken($client_id, $user_id, $scope);
        $this->storage->expireAuthorizationCode($this->authCode['code']);

        return $token;
    }
}
