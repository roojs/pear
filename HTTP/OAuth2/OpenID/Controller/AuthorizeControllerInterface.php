<?php

namespace OAuth2\OpenID\Controller;

require_once 'HTTP/OAuth2/HTTP_OAuth2_RequestInterface';
require_once 'HTTP/OAuth2/HTTP_OAuth2_ResponseInterface';

interface AuthorizeControllerInterface
{
    const RESPONSE_TYPE_ID_TOKEN = 'id_token';
    const RESPONSE_TYPE_TOKEN_ID_TOKEN = 'token id_token';
}
