<?php

require_once 'HTTP/OAuth2/HTTP_OAuth2_RequestInterface';
require_once 'HTTP/OAuth2/HTTP_OAuth2_ResponseInterface';

interface TokenTypeInterface
{
    /**
     * Token type identification string
     *
     * ex: "bearer" or "mac"
     */
    public function getTokenType();

    /**
     * Retrieves the token string from the request object
     */
    public function getAccessTokenParameter(RequestInterface $request, ResponseInterface $response);
}
