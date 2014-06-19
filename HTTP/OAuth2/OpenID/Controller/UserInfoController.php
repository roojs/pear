<?php

require_once 'HTTP/OAuth2/TokenType/TokenTypeInterface.php';
require_once 'HTTP/OAuth2/Storage/AccessTokenInterface.php';
require_once 'HTTP/OAuth2/OpenID/Storage/UserClaimsInterface.php';
require_once 'HTTP/OAuth2/Controller/ResourceController.php';
require_once 'HTTP/OAuth2/ScopeInterface.php';
require_once 'HTTP/OAuth2/RequestInterface.php';
require_once 'HTTP/OAuth2/ResponseInterface.php';

/**
 * @see OAuth2\Controller\UserInfoControllerInterface
 */
class HTTP_OAuth2_OpenID_Controller_UserInfoController extends HTTP_OAuth2_Controller_ResourceController implements HTTP_OAuth2_OpenID_Controller_UserInfoControllerInterface
{
    private $token;

    protected $tokenType;
    protected $tokenStorage;
    protected $userClaimsStorage;
    protected $config;
    protected $scopeUtil;

    public function __construct(HTTP_OAuth2_TokenType_TokenTypeInterface $tokenType, HTTP_OAuth2_Storage_AccessTokenInterface $tokenStorage, HTTP_OAuth2_OpenID_Storage_UserClaimsInterface $userClaimsStorage, $config = array(), HTTP_OAuth2_ScopeInterface $scopeUtil = null)
    {
        $this->tokenType = $tokenType;
        $this->tokenStorage = $tokenStorage;
        $this->userClaimsStorage = $userClaimsStorage;

        $this->config = array_merge(array(
            'www_realm' => 'Service',
        ), $config);

        if (is_null($scopeUtil)) {
            $scopeUtil = new HTTP_OAuth2_Scope();
        }
        $this->scopeUtil = $scopeUtil;
    }

    public function handleUserInfoRequest(HTTP_OAuth2_RequestInterface $request, HTTP_OAuth2_ResponseInterface $response)
    {
        if (!$this->verifyResourceRequest($request, $response, 'openid')) {
            return;
        }

        $token = $this->getToken();
        $claims = $this->userClaimsStorage->getUserClaims($token['user_id'], $token['scope']);
        // The sub Claim MUST always be returned in the UserInfo Response.
        // http://openid.net/specs/openid-connect-core-1_0.html#UserInfoResponse
        $claims += array(
            'sub' => $token['user_id'],
        );
        $response->setParameters($claims);
    }
}
