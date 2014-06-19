<?php

require_once 'HTTP/OAuth2/RequestInterface';
require_once 'HTTP/OAuth2/ResponseInterface';

/**
* This is not yet supported!
*/
class HTTP_OAuth2_TokenType_Mac implements HTTP_OAuth2_TokenType_TokenTypeInterface
{
    public function getTokenType()
    {
        return 'mac';
    }

    public function getAccessTokenParameter(HTTP_OAuth2_RequestInterface $request, HTTP_OAuth2_ResponseInterface $response)
    {
        throw new LogicException("Not supported");
    }
}
