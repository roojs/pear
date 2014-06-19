<?php

require_once 'HTTP/OAuth2/RequestInterface';
require_once 'HTTP/OAuth2/ResponseInterface';

/**
 *  This controller is called when a "resource" is requested.
 *  call verifyResourceRequest in order to determine if the request
 *  contains a valid token.
 *
 *  ex:
 *  > if (!$resourceController->verifyResourceRequest(OAuth2\Request::createFromGlobals(), $response = new OAuth2\Response())) {
 *  >     $response->send(); // authorization failed
 *  >     die();
 *  > }
 *  > return json_encode($resource); // valid token!  Send the stuff!
 *
 */
interface HTTP_OAuth2_Controller_ResourceControllerInterface
{
    public function verifyResourceRequest(HTTP_OAuth2_RequestInterface $request, HTTP_OAuth2_ResponseInterface $response, $scope = null);

    public function getAccessTokenData(HTTP_OAuth2_RequestInterface $request, HTTP_OAuth2_ResponseInterface $response);
}
