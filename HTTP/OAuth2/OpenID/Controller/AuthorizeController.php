<?php

require_once 'HTTP/OAuth2/Controller/HTTP_OAuth2_AuthorizeController';
require_once 'HTTP/OAuth2/HTTP_OAuth2_RequestInterface';
require_once 'HTTP/OAuth2/HTTP_OAuth2_ResponseInterface';

/**
 * @see OAuth2\Controller\AuthorizeControllerInterface
 */
class AuthorizeController extends BaseAuthorizeController implements AuthorizeControllerInterface
{
    private $nonce;

    protected function buildAuthorizeParameters($request, $response, $user_id)
    {
        if (!$params = parent::buildAuthorizeParameters($request, $response, $user_id)) {
            return;
        }

        // Generate an id token if needed.
        if ($this->needsIdToken($this->getScope()) && $this->getResponseType() == self::RESPONSE_TYPE_AUTHORIZATION_CODE) {
            $params['id_token'] = $this->responseTypes['id_token']->createIdToken($this->getClientId(), $user_id, $this->nonce);
        }

        // add the nonce to return with the redirect URI
        $params['nonce'] = $this->nonce;

        return $params;
    }

    public function validateAuthorizeRequest(RequestInterface $request, ResponseInterface $response)
    {
        if (!parent::validateAuthorizeRequest($request, $response)) {
            return false;
        }

        $nonce = $request->query('nonce');

        // Validate required nonce for "id_token" and "token id_token"
        if (!$nonce && in_array($this->getResponseType(), array(self::RESPONSE_TYPE_ID_TOKEN, self::RESPONSE_TYPE_TOKEN_ID_TOKEN))) {
            $response->setError(400, 'invalid_nonce', 'This application requires you specify a nonce parameter');

            return false;
        }

        $this->nonce = $nonce;

        return true;
    }

    protected function getValidResponseTypes()
    {
        return array(
            self::RESPONSE_TYPE_ACCESS_TOKEN,
            self::RESPONSE_TYPE_AUTHORIZATION_CODE,
            self::RESPONSE_TYPE_ID_TOKEN,
            self::RESPONSE_TYPE_TOKEN_ID_TOKEN,
        );
    }

    /**
     * Returns whether the current request needs to generate an id token.
     *
     * ID Tokens are a part of the OpenID Connect specification, so this
     * method checks whether OpenID Connect is enabled in the server settings
     * and whether the openid scope was requested.
     *
     * @param $request_scope
     *  A space-separated string of scopes.
     *
     * @return
     *   TRUE if an id token is needed, FALSE otherwise.
     */
    public function needsIdToken($request_scope)
    {
        // see if the "openid" scope exists in the requested scope
        return $this->scopeUtil->checkScope('openid', $request_scope);
    }

    public function getNonce()
    {
        return $this->nonce;
    }
}
