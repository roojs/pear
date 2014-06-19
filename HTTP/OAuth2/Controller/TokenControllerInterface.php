<?php

require_once 'HTTP/OAuth2/RequestInterface';
require_once 'HTTP/OAuth2/ResponseInterface';

/**
 *  This controller is called when a token is being requested.
 *  it is called to handle all grant types the application supports.
 *  It also validates the client's credentials
 *
 *  ex:
 *  > $tokenController->handleTokenRequest(OAuth2\Request::createFromGlobals(), $response = new OAuth2\Response());
require_once 'HTTP/ *  > $response->send()';
 *
 */
interface HTTP_OAuth2_Controller_TokenControllerInterface
{
    /**
     * handleTokenRequest
     *
     * @param $request
     * OAuth2\RequestInterface - The current http request
     * @param $response
     * OAuth2\ResponseInterface - An instance of OAuth2\ResponseInterface to contain the response data
     *
     */
    public function handleTokenRequest(HTTP_OAuth2_RequestInterface $request, HTTP_OAuth2_ResponseInterface $response);

    public function grantAccessToken(HTTP_OAuth2_RequestInterface $request, HTTP_OAuth2_ResponseInterface $response);
}
