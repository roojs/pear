<?php

require_once 'HTTP/OAuth2/RequestInterface.php';
require_once 'HTTP/OAuth2/ResponseInterface.php';

interface HTTP_OAuth2_OpenID_Controller_AuthorizeControllerInterface
{
    const RESPONSE_TYPE_ID_TOKEN = 'id_token';
    const RESPONSE_TYPE_TOKEN_ID_TOKEN = 'token id_token';
}
