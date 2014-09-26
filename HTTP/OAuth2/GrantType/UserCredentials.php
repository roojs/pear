<?php

require_once 'HTTP/OAuth2/Storage/UserCredentialsInterface.php';
require_once 'HTTP/OAuth2/ResponseType/AccessTokenInterface.php';
require_once 'HTTP/OAuth2/RequestInterface.php';
require_once 'HTTP/OAuth2/ResponseInterface.php';

/**
 *
 * @author Brent Shaffer <bshafs at gmail dot com>
 */
class HTTP_OAuth2_GrantType_UserCredentials implements HTTP_OAuth2_GrantType_GrantTypeInterface
{
    private $userInfo;

    protected $storage;

    /**
     * @param OAuth2\Storage\UserCredentialsInterface $storage
     * REQUIRED Storage class for retrieving user credentials information
     */
    public function __construct(HTTP_OAuth2_Storage_UserCredentialsInterface $storage)
    {
        $this->storage = $storage;
    }

    public function getQuerystringIdentifier()
    {
        return 'password';
    }

    public function validateRequest(HTTP_OAuth2_RequestInterface $request, HTTP_OAuth2_ResponseInterface $response)
    {
        if (!$request->request("password") || !$request->request("username")) {
            $response->setError(400, 'invalid_request', 'Missing parameters: "username" and "password" required');

            return null;
        }
        print_r($this->storage);exit;
        if (!$this->storage->checkUserCredentials($request->request("username"), $request->request("password"))) {
            $response->setError(401, 'invalid_grant', 'Invalid username and password combination');

            return null;
        }

        $userInfo = $this->storage->getUserDetails($request->request("username"));

        if (empty($userInfo)) {
            $response->setError(400, 'invalid_grant', 'Unable to retrieve user information');

            return null;
        }

        if (!isset($userInfo['user_id'])) {
            throw new LogicException("you must set the user_id on the array returned by getUserDetails");
        }

        $this->userInfo = $userInfo;

        return true;
    }

    public function getClientId()
    {
        return null;
    }

    public function getUserId()
    {
        return $this->userInfo['user_id'];
    }

    public function getScope()
    {
        return isset($this->userInfo['scope']) ? $this->userInfo['scope'] : null;
    }

    public function createAccessToken(HTTP_OAuth2_ResponseType_AccessTokenInterface $accessToken, $client_id, $user_id, $scope)
    {
        return $accessToken->createAccessToken($client_id, $user_id, $scope);
    }
}
