<?php

require_once 'HTTP/OAuth2/Controller/ResourceControllerInterface.php';
require_once 'HTTP/OAuth2/Controller/ResourceController.php';
require_once 'HTTP/OAuth2/OpenID/Controller/UserInfoControllerInterface.php';
require_once 'HTTP/OAuth2/OpenID/Controller/UserInfoController.php';
require_once 'HTTP/OAuth2/OpenID/Controller/AuthorizeController.php';
require_once 'HTTP/OAuth2/OpenID/ResponseType/AuthorizationCode.php';
require_once 'HTTP/OAuth2/OpenID/Storage/AuthorizationCodeInterface.php';
require_once 'HTTP/OAuth2/OpenID/GrantType/AuthorizationCode.php';
require_once 'HTTP/OAuth2/Controller/AuthorizeControllerInterface.php';
require_once 'HTTP/OAuth2/Controller/AuthorizeController.php';
require_once 'HTTP/OAuth2/Controller/TokenControllerInterface.php';
require_once 'HTTP/OAuth2/Controller/TokenController.php';
require_once 'HTTP/OAuth2/ClientAssertionType/ClientAssertionTypeInterface.php';
require_once 'HTTP/OAuth2/ClientAssertionType/HttpBasic.php';
require_once 'HTTP/OAuth2/ResponseType/ResponseTypeInterface.php';
require_once 'HTTP/OAuth2/ResponseType/AuthorizationCode.php';
require_once 'HTTP/OAuth2/ResponseType/AccessToken.php';
require_once 'HTTP/OAuth2/ResponseType/CryptoToken.php';
require_once 'HTTP/OAuth2/OpenID/ResponseType/IdToken.php';
require_once 'HTTP/OAuth2/OpenID/ResponseType/TokenIdToken.php';
require_once 'HTTP/OAuth2/TokenType/TokenTypeInterface.php';
require_once 'HTTP/OAuth2/TokenType/Bearer.php';
require_once 'HTTP/OAuth2/GrantType/GrantTypeInterface.php';
require_once 'HTTP/OAuth2/GrantType/UserCredentials.php';
require_once 'HTTP/OAuth2/GrantType/ClientCredentials.php';
require_once 'HTTP/OAuth2/GrantType/RefreshToken.php';
require_once 'HTTP/OAuth2/GrantType/AuthorizationCode.php';
require_once 'HTTP/OAuth2/Storage/CryptoToken.php';
require_once 'HTTP/OAuth2/Storage/CryptoTokenInterface.php';
require_once 'HTTP/OAuth2/Response.php';

/**
* Server class for OAuth2
* This class serves as a convience class which wraps the other Controller classes
*
* @see OAuth2\Controller\ResourceController
* @see OAuth2\Controller\AuthorizeController
* @see OAuth2\Controller\TokenController
*/
class HTTP_OAuth2_Server implements HTTP_OAuth2_Controller_ResourceControllerInterface,
    HTTP_OAuth2_Controller_AuthorizeControllerInterface,
    HTTP_OAuth2_Controller_TokenControllerInterface,
    HTTP_OAuth2_OpenID_Controller_UserInfoControllerInterface
{
    // misc properties
    protected $response;
    protected $config;
    protected $storages;

    // servers
    protected $authorizeController;
    protected $tokenController;
    protected $resourceController;
    protected $userInfoController;

    // config classes
    protected $grantTypes;
    protected $responseTypes;
    protected $tokenType;
    protected $scopeUtil;
    protected $clientAssertionType;

    protected $storageMap = array(
        'access_token' => 'HTTP_OAuth2_Storage_AccessTokenInterface',
        'authorization_code' => 'HTTP_OAuth2_Storage_AuthorizationCodeInterface',
        'client_credentials' => 'HTTP_OAuth2_Storage_ClientCredentialsInterface',
        'client' => 'HTTP_OAuth2_Storage_ClientInterface',
        'refresh_token' => 'HTTP_OAuth2_Storage_RefreshTokenInterface',
        'user_credentials' => 'HTTP_OAuth2_Storage_UserCredentialsInterface',
        'user_claims' => 'HTTP_OAuth2_OpenID_Storage_UserClaimsInterface',
        'public_key' => 'HTTP_OAuth2_Storage_PublicKeyInterface',
        'jwt_bearer' => 'HTTP_OAuth2_Storage_JWTBearerInterface',
        'scope' => 'HTTP_OAuth2_Storage_ScopeInterface',
    );
    protected $responseTypeMap = array(
        'token' => 'HTTP_OAuth2_ResponseType_AccessTokenInterface',
        'code' => 'HTTP_OAuth2_ResponseType_AuthorizationCodeInterface',
        'id_token' => 'HTTP_OAuth2_OpenID_ResponseType_IdTokenInterface',
        'token id_token' => 'HTTP_OAuth2_OpenID_ResponseType_TokenIdTokenInterface',
    );

    /**
     * @param mixed $storage
     * array - array of Objects to implement storage
     * OAuth2\Storage object implementing all required storage types (ClientCredentialsInterface and AccessTokenInterface as a minimum)
     * @param array $config
     * specify a different token lifetime, token header name, etc
     * @param array $grantTypes
     * An array of OAuth2\GrantType\GrantTypeInterface to use for granting access tokens
     * @param array $responseTypes
     * Response types to use.  array keys should be "code" and and "token" for
     * Access Token and Authorization Code response types
     * @param OAuth2\TokenType\TokenTypeInterface $tokenType
     * The token type object to use. Valid token types are "bearer" and "mac"
     * @param OAuth2\ScopeInterface $scopeUtil
     * The scope utility class to use to validate scope
     * @param OAuth2\ClientAssertionType\ClientAssertionTypeInterface $clientAssertionType
     * The method in which to verify the client identity.  Default is HttpBasic
     *
     * @ingroup oauth2_section_7
     */
    public function __construct($storage = array(), array $config = array(), array $grantTypes = array(), array $responseTypes = array(), HTTP_OAuth2_TokenType_TokenTypeInterface $tokenType = null, HTTP_OAuth2_ScopeInterface $scopeUtil = null, HTTP_OAuth2_ClientAssertionType_ClientAssertionTypeInterface $clientAssertionType = null)
    {
        $storage = is_array($storage) ? $storage : array($storage);
        $this->storages = array();
        foreach ($storage as $key => $service) {
            $this->addStorage($service, $key);
        }

        // merge all config values.  These get passed to our controller objects
        $this->config = array_merge(array(
            'use_crypto_tokens'        => false,
            'store_encrypted_token_string' => true,
            'use_openid_connect'       => false,
            'id_lifetime'              => 3600,
            'access_lifetime'          => 3600,
            'www_realm'                => 'Service',
            'token_param_name'         => 'access_token',
            'token_bearer_header_name' => 'Bearer',
            'enforce_state'            => true,
            'require_exact_redirect_uri' => true,
            'allow_implicit'           => false,
            'allow_credentials_in_request_body' => true,
            'allow_public_clients'     => true,
            'always_issue_new_refresh_token' => false,
        ), $config);

        foreach ($grantTypes as $key => $grantType) {
            $this->addGrantType($grantType, $key);
        }
        foreach ($responseTypes as $key => $responseType) {
            $this->addResponseType($responseType, $key);
        }
        $this->tokenType = $tokenType;
        $this->scopeUtil = $scopeUtil;
        $this->clientAssertionType = $clientAssertionType;
    }

    public function getAuthorizeController()
    {
        if (is_null($this->authorizeController)) {
            $this->authorizeController = $this->createDefaultAuthorizeController();
        }

        return $this->authorizeController;
    }

    public function getTokenController()
    {
        if (is_null($this->tokenController)) {
            $this->tokenController = $this->createDefaultTokenController();
        }

        return $this->tokenController;
    }

    public function getResourceController()
    {
        if (is_null($this->resourceController)) {
            $this->resourceController = $this->createDefaultResourceController();
        }

        return $this->resourceController;
    }

    public function getUserInfoController()
    {
        if (is_null($this->userInfoController)) {
            $this->userInfoController = $this->createDefaultUserInfoController();
        }

        return $this->userInfoController;
    }

    /**
     * every getter deserves a setter
     */
    public function setAuthorizeController(HTTP_OAuth2_Controller_AuthorizeControllerInterface $authorizeController)
    {
        $this->authorizeController = $authorizeController;
    }

    /**
     * every getter deserves a setter
     */
    public function setTokenController(HTTP_OAuth2_Controller_TokenControllerInterface $tokenController)
    {
        $this->tokenController = $tokenController;
    }

    /**
     * every getter deserves a setter
     */
    public function setResourceController(HTTP_OAuth2_Controller_ResourceControllerInterface $resourceController)
    {
        $this->resourceController = $resourceController;
    }

    /**
     * every getter deserves a setter
     */
    public function setUserInfoController(HTTP_OAuth2_OpenID_Controller_UserInfoControllerInterface $userInfoController)
    {
        $this->userInfoController = $userInfoController;
    }

    /**
     * Return claims about the authenticated end-user.
     * This would be called from the "/UserInfo" endpoint as defined in the spec.
     *
     * @param $request - OAuth2\RequestInterface
     * Request object to grant access token
     *
     * @param $response - OAuth2\ResponseInterface
     * Response object containing error messages (failure) or user claims (success)
     *
     * @throws InvalidArgumentException
     * @throws LogicException
     *
     * @see http://openid.net/specs/openid-connect-core-1_0.html#UserInfo
     */
    public function handleUserInfoRequest(HTTP_OAuth2_RequestInterface $request, HTTP_OAuth2_ResponseInterface $response = null)
    {
        $this->response = is_null($response) ? new HTTP_OAuth2_Response() : $response;
        $this->getUserInfoController()->handleUserInfoRequest($request, $this->response);

        return $this->response;
    }

    /**
     * Grant or deny a requested access token.
     * This would be called from the "/token" endpoint as defined in the spec.
     * Obviously, you can call your endpoint whatever you want.
     *
     * @param $request - OAuth2\RequestInterface
     * Request object to grant access token
     *
     * @param $response - OAuth2\ResponseInterface
     * Response object containing error messages (failure) or access token (success)
     *
     * @throws InvalidArgumentException
     * @throws LogicException
     *
     * @see http://tools.ietf.org/html/rfc6749#section-4
     * @see http://tools.ietf.org/html/rfc6749#section-10.6
     * @see http://tools.ietf.org/html/rfc6749#section-4.1.3
     *
     * @ingroup oauth2_section_4
     */
    public function handleTokenRequest(HTTP_OAuth2_RequestInterface $request, HTTP_OAuth2_ResponseInterface $response = null)
    {
        $this->response = is_null($response) ? new HTTP_OAuth2_Response() : $response;
        $this->getTokenController()->handleTokenRequest($request, $this->response);

        return $this->response;
    }

    public function grantAccessToken(HTTP_OAuth2_RequestInterface $request, HTTP_OAuth2_ResponseInterface $response = null)
    {
        $this->response = is_null($response) ? new HTTP_OAuth2_Response() : $response;
        $value = $this->getTokenController()->grantAccessToken($request, $this->response);

        return $value;
    }

    /**
     * Redirect the user appropriately after approval.
     *
     * After the user has approved or denied the resource request the
     * authorization server should call this function to redirect the user
     * appropriately.
     *
     * @param $request
     * The request should have the follow parameters set in the querystring:
     * - response_type: The requested response: an access token, an
     * authorization code, or both.
     * - client_id: The client identifier as described in Section 2.
     * - redirect_uri: An absolute URI to which the authorization server
     * will redirect the user-agent to when the end-user authorization
     * step is completed.
     * - scope: (optional) The scope of the resource request expressed as a
     * list of space-delimited strings.
     * - state: (optional) An opaque value used by the client to maintain
     * state between the request and callback.
     * @param $is_authorized
     * TRUE or FALSE depending on whether the user authorized the access.
     * @param $user_id
     * Identifier of user who authorized the client
     *
     * @see http://tools.ietf.org/html/rfc6749#section-4
     *
     * @ingroup oauth2_section_4
     */
    public function handleAuthorizeRequest(HTTP_OAuth2_RequestInterface $request, HTTP_OAuth2_ResponseInterface $response, $is_authorized, $user_id = null)
    {
        $this->response = $response;
        $this->getAuthorizeController()->handleAuthorizeRequest($request, $this->response, $is_authorized, $user_id);

        return $this->response;
    }

    /**
     * Pull the authorization request data out of the HTTP request.
     * - The redirect_uri is OPTIONAL as per draft 20. But your implementation can enforce it
     * by setting $config['enforce_redirect'] to true.
     * - The state is OPTIONAL but recommended to enforce CSRF. Draft 21 states, however, that
     * CSRF protection is MANDATORY. You can enforce this by setting the $config['enforce_state'] to true.
     *
     * The draft specifies that the parameters should be retrieved from GET, override the Response
     * object to change this
     *
     * @return
     * The authorization parameters so the authorization server can prompt
     * the user for approval if valid.
     *
     * @see http://tools.ietf.org/html/rfc6749#section-4.1.1
     * @see http://tools.ietf.org/html/rfc6749#section-10.12
     *
     * @ingroup oauth2_section_3
     */
    public function validateAuthorizeRequest(HTTP_OAuth2_RequestInterface $request, HTTP_OAuth2_ResponseInterface $response = null)
    {
        $this->response = is_null($response) ? new HTTP_OAuth2_Response() : $response;
        $value = $this->getAuthorizeController()->validateAuthorizeRequest($request, $this->response);

        return $value;
    }

    public function verifyResourceRequest(HTTP_OAuth2_RequestInterface $request, HTTP_OAuth2_ResponseInterface $response = null, $scope = null)
    {
        $this->response = is_null($response) ? new HTTP_OAuth2_Response() : $response;
        $value = $this->getResourceController()->verifyResourceRequest($request, $this->response, $scope);

        return $value;
    }

    public function getAccessTokenData(HTTP_OAuth2_RequestInterface $request, HTTP_OAuth2_ResponseInterface $response = null)
    {
        $this->response = is_null($response) ? new HTTP_OAuth2_Response() : $response;
        $value = $this->getResourceController()->getAccessTokenData($request, $this->response);

        return $value;
    }

    public function addGrantType(HTTP_OAuth2_GrantType_GrantTypeInterface $grantType, $key = null)
    {
        if (is_string($key)) {
            $this->grantTypes[$key] = $grantType;
        } else {
            $this->grantTypes[$grantType->getQuerystringIdentifier()] = $grantType;
        }

        // persist added grant type down to TokenController
        if (!is_null($this->tokenController)) {
            $this->getTokenController()->addGrantType($grantType);
        }
    }

    /**
     * Set a storage object for the server
     *
     * @param $storage
     * An object implementing one of the Storage interfaces
     * @param $key
     * If null, the storage is set to the key of each storage interface it implements
     *
     * @see storageMap
     */
    public function addStorage($storage, $key = null)
    {
        // if explicitly set to a valid key, do not "magically" set below
        if (isset($this->storageMap[$key])) {
            if (!is_null($storage) && !$storage instanceof $this->storageMap[$key]) {
                throw new InvalidArgumentException(sprintf('storage of type "%s" must implement interface "%s"', $key, $this->storageMap[$key]));
            }
            $this->storages[$key] = $storage;

            // special logic to handle "client" and "client_credentials" strangeness
            if ($key === 'client' && !isset($this->storages['client_credentials'])) {
                if ($storage instanceof HTTP_OAuth2_Storage_ClientCredentialsInterface) {
                    $this->storages['client_credentials'] = $storage;
                }
            } elseif ($key === 'client_credentials' && !isset($this->storages['client'])) {
                if ($storage instanceof HTTP_OAuth2_Storage_ClientInterface) {
                    $this->storages['client'] = $storage;
                }
            }
        } elseif (!is_null($key) && !is_numeric($key)) {
            throw new InvalidArgumentException(sprintf('unknown storage key "%s", must be one of [%s]', $key, implode(', ', array_keys($this->storageMap))));
        } else {
            $set = false;
            foreach ($this->storageMap as $type => $interface) {
                if ($storage instanceof $interface) {
                    $this->storages[$type] = $storage;
                    $set = true;
                }
            }

            if (!$set) {
                throw new InvalidArgumentException(sprintf('storage of class "%s" must implement one of [%s]', get_class($storage), implode(', ', $this->storageMap)));
            }
        }
    }

    public function addResponseType(HTTP_OAuth2_ResponseType_ResponseTypeInterface $responseType, $key = null)
    {
        if (isset($this->responseTypeMap[$key])) {
            if (!$responseType instanceof $this->responseTypeMap[$key]) {
                throw new InvalidArgumentException(sprintf('responseType of type "%s" must implement interface "%s"', $key, $this->responseTypeMap[$key]));
            }
            $this->responseTypes[$key] = $responseType;
        } elseif (!is_null($key) && !is_numeric($key)) {
            throw new InvalidArgumentException(sprintf('unknown responseType key "%s", must be one of [%s]', $key, implode(', ', array_keys($this->responseTypeMap))));
        } else {
            $set = false;
            foreach ($this->responseTypeMap as $type => $interface) {
                if ($responseType instanceof $interface) {
                    $this->responseTypes[$type] = $responseType;
                    $set = true;
                }
            }

            if (!$set) {
                throw new InvalidArgumentException(sprintf('Unknown response type %s.  Please implement one of [%s]', get_class($responseType), implode(', ', $this->responseTypeMap)));
            }
        }
    }

    public function getScopeUtil()
    {
        if (!$this->scopeUtil) {
            $storage = isset($this->storages['scope']) ? $this->storages['scope'] : null;
            $this->scopeUtil = new HTTP_OAuth2_Scope($storage);
        }

        return $this->scopeUtil;
    }

    /**
     * every getter deserves a setter
     */
    public function setScopeUtil($scopeUtil)
    {
        $this->scopeUtil = $scopeUtil;
    }

    protected function createDefaultAuthorizeController()
    {
        if (!isset($this->storages['client'])) {
            throw new LogicException("You must supply a storage object implementing OAuth2\Storage\ClientInterface to use the authorize server");
        }
        if (0 == count($this->responseTypes)) {
            $this->responseTypes = $this->getDefaultResponseTypes();
        }
        if ($this->config['use_openid_connect'] && !isset($this->responseTypes['id_token'])) {
            $this->responseTypes['id_token'] = $this->createDefaultIdTokenResponseType();
            if ($this->config['allow_implicit']) {
                $this->responseTypes['token id_token'] = $this->createDefaultTokenIdTokenResponseType();
            }
        }

        $config = array_intersect_key($this->config, array_flip(explode(' ', 'allow_implicit enforce_state require_exact_redirect_uri')));

        if ($this->config['use_openid_connect']) {
            return new HTTP_OAuth2_OpenID_Controller_AuthorizeController($this->storages['client'], $this->responseTypes, $config, $this->getScopeUtil());
        }

        return new HTTP_OAuth2_Controller_AuthorizeController($this->storages['client'], $this->responseTypes, $config, $this->getScopeUtil());
    }

    protected function createDefaultTokenController()
    {
        if (0 == count($this->grantTypes)) {
            $this->grantTypes = $this->getDefaultGrantTypes();
        }

        if (is_null($this->clientAssertionType)) {
            // see if HttpBasic assertion type is requred.  If so, then create it from storage classes.
            foreach ($this->grantTypes as $grantType) {
                if (!$grantType instanceof HTTP_OAuth2_ClientAssertionType_ClientAssertionTypeInterface) {
                    if (!isset($this->storages['client_credentials'])) {
                        throw new LogicException("You must supply a storage object implementing OAuth2\Storage\ClientCredentialsInterface to use the token server");
                    }
                    $config = array_intersect_key($this->config, array_flip(explode(' ', 'allow_credentials_in_request_body allow_public_clients')));
                    $this->clientAssertionType = new HTTP_OAuth2_ClientAssertionType_HttpBasic($this->storages['client_credentials'], $config);
                    break;
                }
            }
        }
        print_r($this->clientAssertionType);exit;
        if (!isset($this->storages['client'])) {
            throw new LogicException("You must supply a storage object implementing OAuth2\Storage\ClientInterface to use the token server");
        }

        $accessTokenResponseType = $this->getAccessTokenResponseType();

        return new HTTP_OAuth2_Controller_TokenController($accessTokenResponseType, $this->storages['client'], $this->grantTypes, $this->clientAssertionType, $this->getScopeUtil());
    }

    protected function createDefaultResourceController()
    {
        if ($this->config['use_crypto_tokens']) {
            // overwrites access token storage with crypto token storage if "use_crypto_tokens" is set
            if (!isset($this->storages['access_token']) || !$this->storages['access_token'] instanceof HTTP_OAuth2_Storage_CryptoTokenInterface) {
                $this->storages['access_token'] = $this->createDefaultCryptoTokenStorage();
            }
        } elseif (!isset($this->storages['access_token'])) {
            throw new LogicException("You must supply a storage object implementing OAuth2\Storage\AccessTokenInterface or use CryptoTokens to use the resource server");
        }

        if (!$this->tokenType) {
            $this->tokenType = $this->getDefaultTokenType();
        }

        $config = array_intersect_key($this->config, array('www_realm' => ''));

        return new HTTP_OAuth2_Controller_ResourceController($this->tokenType, $this->storages['access_token'], $config, $this->getScopeUtil());
    }

    protected function createDefaultUserInfoController()
    {
        if ($this->config['use_crypto_tokens']) {
            // overwrites access token storage with crypto token storage if "use_crypto_tokens" is set
            if (!isset($this->storages['access_token']) || !$this->storages['access_token'] instanceof HTTP_OAuth2_Storage_CryptoTokenInterface) {
                $this->storages['access_token'] = $this->createDefaultCryptoTokenStorage();
            }
        } elseif (!isset($this->storages['access_token'])) {
            throw new LogicException("You must supply a storage object implementing OAuth2\Storage\AccessTokenInterface or use CryptoTokens to use the UserInfo server");
        }

        if (!isset($this->storages['user_claims'])) {
            throw new LogicException("You must supply a storage object implementing OAuth2\OpenID\Storage\UserClaimsInterface to use the UserInfo server");
        }

        if (!$this->tokenType) {
            $this->tokenType = $this->getDefaultTokenType();
        }

        $config = array_intersect_key($this->config, array('www_realm' => ''));

        return new HTTP_OAuth2_OpenID_Controller_UserInfoController($this->tokenType, $this->storages['access_token'], $this->storages['user_claims'], $config, $this->getScopeUtil());
    }

    protected function getDefaultTokenType()
    {
        $config = array_intersect_key($this->config, array_flip(explode(' ', 'token_param_name token_bearer_header_name')));

        return new HTTP_OAuth2_TokenType_Bearer($config);
    }

    protected function getDefaultResponseTypes()
    {
        $responseTypes = array();

        if ($this->config['allow_implicit']) {
            $responseTypes['token'] = $this->getAccessTokenResponseType();
        }

        if ($this->config['use_openid_connect']) {
            $responseTypes['id_token'] = $this->getIdTokenResponseType();
            if ($this->config['allow_implicit']) {
                $responseTypes['token id_token'] = $this->getTokenIdTokenResponseType();
            }
        }

        if (isset($this->storages['authorization_code'])) {
            $config = array_intersect_key($this->config, array_flip(explode(' ', 'enforce_redirect auth_code_lifetime')));
            if ($this->config['use_openid_connect']) {
                if (!$this->storages['authorization_code'] instanceof HTTP_OAuth2_OpenID_Storage_AuthorizationCodeInterface) {
                    throw new LogicException("Your authorization_code storage must implement OAuth2\OpenID\Storage\AuthorizationCodeInterface to work when 'use_openid_connect' is true");
                }
                $responseTypes['code'] = new HTTP_OAuth2_OpenID_ResponseType_AuthorizationCode($this->storages['authorization_code'], $config);
            } else {
                $responseTypes['code'] = new HTTP_OAuth2_ResponseType_AuthorizationCode($this->storages['authorization_code'], $config);
            }
        }

        if (count($responseTypes) == 0) {
            throw new LogicException("You must supply an array of response_types in the constructor or implement a OAuth2\Storage\AuthorizationCodeInterface storage object or set 'allow_implicit' to true and implement a OAuth2\Storage\AccessTokenInterface storage object");
        }

        return $responseTypes;
    }

    protected function getDefaultGrantTypes()
    {
        $grantTypes = array();

        if (isset($this->storages['user_credentials'])) {
            $grantTypes['password'] = new HTTP_OAuth2_GrantType_UserCredentials($this->storages['user_credentials']);
        }

        if (isset($this->storages['client_credentials'])) {
            $config = array_intersect_key($this->config, array('allow_credentials_in_request_body' => ''));
            $grantTypes['client_credentials'] = new HTTP_OAuth2_GrantType_ClientCredentials($this->storages['client_credentials'], $config);
        }

        if (isset($this->storages['refresh_token'])) {
            $config = array_intersect_key($this->config, array('always_issue_new_refresh_token' => ''));
            $grantTypes['refresh_token'] = new HTTP_OAuth2_GrantType_RefreshToken($this->storages['refresh_token'], $config);
        }

        if (isset($this->storages['authorization_code'])) {
            if ($this->config['use_openid_connect']) {
                if (!$this->storages['authorization_code'] instanceof HTTP_OAuth2_OpenID_Storage_AuthorizationCodeInterface) {
                    throw new LogicException("Your authorization_code storage must implement OAuth2\OpenID\Storage\AuthorizationCodeInterface to work when 'use_openid_connect' is true");
                }
                $grantTypes['authorization_code'] = new HTTP_OAuth2_OpenID_GrantType_AuthorizationCode($this->storages['authorization_code']);
            } else {
                $grantTypes['authorization_code'] = new HTTP_OAuth2_GrantType_AuthorizationCode($this->storages['authorization_code']);
            }
        }

        if (count($grantTypes) == 0) {
            throw new LogicException("Unable to build default grant types - You must supply an array of grant_types in the constructor");
        }

        return $grantTypes;
    }

    protected function getAccessTokenResponseType()
    {
        if (isset($this->responseTypes['token'])) {
            return $this->responseTypes['token'];
        }

        if ($this->config['use_crypto_tokens']) {
            return $this->createDefaultCryptoTokenResponseType();
        }

        return $this->createDefaultAccessTokenResponseType();
    }

    protected function getIdTokenResponseType()
    {
        if (isset($this->responseTypes['id_token'])) {
            return $this->responseTypes['id_token'];
        }

        return $this->createDefaultIdTokenResponseType();
    }

    protected function getTokenIdTokenResponseType()
    {
        if (isset($this->responseTypes['token id_token'])) {
            return $this->responseTypes['token id_token'];
        }

        return $this->createDefaultTokenIdTokenResponseType();
    }

    /**
     * For Resource Controller
     */
    protected function createDefaultCryptoTokenStorage()
    {
        if (!isset($this->storages['public_key'])) {
            throw new LogicException("You must supply a storage object implementing OAuth2\Storage\PublicKeyInterface to use crypto tokens");
        }
        $tokenStorage = null;
        if (!empty($this->config['store_encrypted_token_string']) && isset($this->storages['access_token'])) {
            $tokenStorage = $this->storages['access_token'];
        }
        // wrap the access token storage as required.
        return new HTTP_OAuth2_Storage_CryptoToken($this->storages['public_key'], $tokenStorage);
    }

    /**
     * For Authorize and Token Controllers
     */
    protected function createDefaultCryptoTokenResponseType()
    {
        if (!isset($this->storages['public_key'])) {
            throw new LogicException("You must supply a storage object implementing OAuth2\Storage\PublicKeyInterface to use crypto tokens");
        }

        $tokenStorage = null;
        if (isset($this->storages['access_token'])) {
            $tokenStorage = $this->storages['access_token'];
        }

        $refreshStorage = null;
        if (isset($this->storages['refresh_token'])) {
            $refreshStorage = $this->storages['refresh_token'];
        }

        $config = array_intersect_key($this->config, array_flip(explode(' ', 'store_encrypted_token_string')));

        return new HTTP_OAuth2_ResponseType_CryptoToken($this->storages['public_key'], $tokenStorage, $refreshStorage, $config);
    }

    protected function createDefaultAccessTokenResponseType()
    {
        if (!isset($this->storages['access_token'])) {
            throw new LogicException("You must supply a response type implementing OAuth2\ResponseType\AccessTokenInterface, or a storage object implementing OAuth2\Storage\AccessTokenInterface to use the token server");
        }

        $refreshStorage = null;
        if (isset($this->storages['refresh_token'])) {
            $refreshStorage = $this->storages['refresh_token'];
        }

        $config = array_intersect_key($this->config, array_flip(explode(' ', 'access_lifetime refresh_token_lifetime')));
        $config['token_type'] = $this->tokenType ? $this->tokenType->getTokenType() :  $this->getDefaultTokenType()->getTokenType();

        return new HTTP_OAuth2_ResponseType_AccessToken($this->storages['access_token'], $refreshStorage, $config);
    }

    protected function createDefaultIdTokenResponseType()
    {
        if (!isset($this->storages['user_claims'])) {
            throw new LogicException("You must supply a storage object implementing OAuth2\OpenID\Storage\UserClaimsInterface to use openid connect");
        }
        if (!isset($this->storages['public_key'])) {
            throw new LogicException("You must supply a storage object implementing OAuth2\Storage\PublicKeyInterface to use openid connect");
        }

        $config = array_intersect_key($this->config, array_flip(explode(' ', 'issuer id_lifetime')));
        return new HTTP_OAuth2_OpenID_ResponseType_IdToken($this->storages['user_claims'], $this->storages['public_key'], $config);
    }

    protected function createDefaultTokenIdTokenResponseType()
    {
        return new HTTP_OAuth2_OpenID_ResponseType_TokenIdToken($this->getAccessTokenResponseType(), $this->getIdTokenResponseType());
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getStorages()
    {
        return $this->storages;
    }

    public function getStorage($name)
    {
        return isset($this->storages[$name]) ? $this->storages[$name] : null;
    }

    public function getGrantTypes()
    {
        return $this->grantTypes;
    }

    public function getGrantType($name)
    {
        return isset($this->grantTypes[$name]) ? $this->grantTypes[$name] : null;
    }

    public function getResponseTypes()
    {
        return $this->responseTypes;
    }

    public function getResponseType($name)
    {
        return isset($this->responseTypes[$name]) ? $this->responseTypes[$name] : null;
    }

    public function getTokenType()
    {
        return $this->tokenType;
    }

    public function getClientAssertionType()
    {
        return $this->clientAssertionType;
    }

    public function setConfig($name, $value)
    {
        $this->config[$name] = $value;
    }

    public function getConfig($name, $default = null)
    {
        return isset($this->config[$name]) ? $this->config[$name] : $default;
    }
}
