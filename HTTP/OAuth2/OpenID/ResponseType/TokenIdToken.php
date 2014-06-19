<?php

require_once 'HTTP/OAuth2/ResponseType/AccessTokenInterface';
require_once 'HTTP/OAuth2/ResponseType/ResponseTypeInterface';

class HTTP_OAuth2_ResponseType_TokenIdToken implements HTTP_OAuth2_ResponseType_TokenIdTokenInterface
{
    protected $accessToken;
    protected $idToken;

    public function __construct(HTTP_OAuth2_ResponseType_AccessTokenInterface $accessToken, HTTP_OAuth2_ResponseType_IdToken $idToken)
    {
        $this->accessToken = $accessToken;
        $this->idToken = $idToken;
    }

    public function getAuthorizeResponse($params, $user_id = null)
    {
        $result = $this->accessToken->getAuthorizeResponse($params, $user_id);
        $access_token = $result[1]['fragment']['access_token'];
        $id_token = $this->idToken->createIdToken($params['client_id'], $user_id, $params['nonce'], null, $access_token);
        $result[1]['fragment']['id_token'] = $id_token;

        return $result;
    }
}
