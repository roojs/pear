<?php

require_once 'HTTP/OAuth2/RequestInterface.php';
require_once 'HTTP/OAuth2/ResponseInterface.php';

/**
 * Interface for all OAuth2 Client Assertion Types
 */
interface HTTP_OAuth2_ClientAssertionType_ClientAssertionTypeInterface
{
    public function validateRequest(HTTP_OAuth2_RequestInterface $request, HTTP_OAuth2_ResponseInterface $response);
    public function getClientId();
}
