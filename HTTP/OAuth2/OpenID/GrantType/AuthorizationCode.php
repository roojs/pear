<?php

require_once 'HTTP/OAuth2/GrantType/AuthorizationCode.php';
require_once 'HTTP/OAuth2/Storage/AuthorizationCodeInterface.php';
require_once 'HTTP/OAuth2/ResponseType/AccessTokenInterface.php';
require_once 'HTTP/OAuth2/RequestInterface.php';
require_once 'HTTP/OAuth2/ResponseInterface.php';

/**
 *
 * @author Brent Shaffer <bshafs at gmail dot com>
 */
class HTTP_OAuth2_OpenID_GrantType_AuthorizationCode extends HTTP_OAuth2_GrantType_AuthorizationCode
{
    public function createAccessToken(HTTP_OAuth2_ResponseType_AccessTokenInterface $accessToken, $client_id, $user_id, $scope)
    {
        $includeRefreshToken = true;
        if (isset($this->authCode['id_token'])) {
            // OpenID Connect requests include the refresh token only if the
            // offline_access scope has been requested and granted.
            $scopes = explode(' ', trim($scope));
            $includeRefreshToken = in_array('offline_access', $scopes);
        }

        $token = $accessToken->createAccessToken($client_id, $user_id, $scope, $includeRefreshToken);
        if (isset($this->authCode['id_token'])) {
            $token['id_token'] = $this->authCode['id_token'];
        }

        $this->storage->expireAuthorizationCode($this->authCode['code']);

        return $token;
    }
}
