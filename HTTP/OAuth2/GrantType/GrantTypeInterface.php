<?php

require_once 'HTTP/OAuth2/ResponseType/AccessTokenInterface.php';
require_once 'HTTP/OAuth2/RequestInterface.php';
require_once 'HTTP/OAuth2/ResponseInterface.php';

/**
 * Interface for all OAuth2 Grant Types
 */
interface HTTP_OAuth2_GrantType_GrantTypeInterface
{
    public function getQuerystringIdentifier();
    public function validateRequest(HTTP_OAuth2_RequestInterface $request, HTTP_OAuth2_ResponseInterface $response);
    public function getClientId();
    public function getUserId();
    public function getScope();
    public function createAccessToken(HTTP_OAuth2_ResponseType_AccessTokenInterface $accessToken, $client_id, $user_id, $scope);
}
