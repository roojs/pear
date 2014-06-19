<?php

require_once 'HTTP/OAuth2/RequestInterface';
require_once 'HTTP/OAuth2/ResponseInterface';

/**
 *  This controller is called when a user should be authorized
 *  by an authorization server.  As OAuth2 does not handle
 *  authorization directly, this controller ensures the request is valid, but
 *  requires the application to determine the value of $is_authorized
 *
 *  ex:
 *  > $user_id = $this->somehowDetermineUserId();
require_once 'HTTP/ *  > $is_authorized = $this->somehowDetermineUserAuthorization()';
require_once 'HTTP/ *  > $response = new OAuth2/Response()';
 *  > $authorizeController->handleAuthorizeRequest(
 *  >     OAuth2\Request::createFromGlobals(),
 *  >     $response,
 *  >     $is_authorized,
 *  >     $user_id);
require_once 'HTTP/ *  > $response->send()';
 *
 */
interface HTTP_OAuth2_Controller_AuthorizeControllerInterface
{
    /**
     * List of possible authentication response types.
     * The "authorization_code" mechanism exclusively supports 'code'
     * and the "implicit" mechanism exclusively supports 'token'.
     *
     * @var string
     * @see http://tools.ietf.org/html/rfc6749#section-4.1.1
     * @see http://tools.ietf.org/html/rfc6749#section-4.2.1
     */
    const RESPONSE_TYPE_AUTHORIZATION_CODE = 'code';
    const RESPONSE_TYPE_ACCESS_TOKEN = 'token';

    public function handleAuthorizeRequest(HTTP_OAuth2_RequestInterface $request, HTTP_OAuth2_ResponseInterface $response, $is_authorized, $user_id = null);

    public function validateAuthorizeRequest(HTTP_OAuth2_RequestInterface $request, HTTP_OAuth2_ResponseInterface $response);
}
