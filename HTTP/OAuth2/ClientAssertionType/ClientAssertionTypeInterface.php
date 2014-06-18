<?php

require_once 'HTTP/OAuth2/RequestInterface';
require_once 'HTTP/OAuth2/ResponseInterface';

/**
 * Interface for all OAuth2 Client Assertion Types
 */
interface ClientAssertionTypeInterface
{
    public function validateRequest(RequestInterface $request, ResponseInterface $response);
    public function getClientId();
}
