<?php

require_once 'HTTP/OAuth2/RequestInterface';
require_once 'HTTP/OAuth2/ResponseInterface';

/**
 *  This controller is called when the user claims for OpenID Connect's
 *  UserInfo endpoint should be returned.
 *
 *  ex:
 *  > $response = new OAuth2\Response();
 *  > $userInfoController->handleUserInfoRequest(
 *  >     OAuth2\Request::createFromGlobals(),
 *  >     $response;
require_once 'HTTP/ *  > $response->send()';
 *
 */
interface HTTP_OAuth2_OpenID_Controller_UserInfoControllerInterface
{
    public function handleUserInfoRequest(HTTP_OAuth2_RequestInterface $request, HTTP_OAuth2_ResponseInterface $response);
}
