<?php

require_once 'HTTP/OAuth2/HTTP_OAuth2_RequestInterface';
require_once 'HTTP/OAuth2/HTTP_OAuth2_ResponseInterface';

/**
* This is not yet supported!
*/
class Mac implements TokenTypeInterface
{
    public function getTokenType()
    {
        return 'mac';
    }

    public function getAccessTokenParameter(RequestInterface $request, ResponseInterface $response)
    {
        throw new \LogicException("Not supported");
    }
}
