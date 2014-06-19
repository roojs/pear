<?php

require_once 'HTTP/OAuth2/ResponseType/AuthorizationCode';
require_once 'HTTP/OAuth2/OpenID/Storage/AuthorizationCodeInterface';

/**
 *
 * @author Brent Shaffer <bshafs at gmail dot com>
 */
class HTTP_OAuth2_OpenID_ResponseType_AuthorizationCode extends HTTP_OAuth2_ResponseType_AuthorizationCode implements HTTP_OAuth2_OpenID_ResponseType_AuthorizationCodeInterface
{
    public function __construct(HTTP_OAuth2_OpenID_Storage_AuthorizationCodeInterface $storage, array $config = array())
    {
        parent::__construct($storage, $config);
    }

    public function getAuthorizeResponse($params, $user_id = null)
    {
        // build the URL to redirect to
        $result = array('query' => array());

        $params += array('scope' => null, 'state' => null, 'id_token' => null);

        $result['query']['code'] = $this->createAuthorizationCode($params['client_id'], $user_id, $params['redirect_uri'], $params['scope'], $params['id_token']);

        if (isset($params['state'])) {
            $result['query']['state'] = $params['state'];
        }

        return array($params['redirect_uri'], $result);
    }

    /**
     * Handle the creation of the authorization code.
     *
     * @param $client_id
     * Client identifier related to the authorization code
     * @param $user_id
     * User ID associated with the authorization code
     * @param $redirect_uri
     * An absolute URI to which the authorization server will redirect the
     * user-agent to when the end-user authorization step is completed.
     * @param $scope
     * (optional) Scopes to be stored in space-separated string.
     * @param $id_token
     * (optional) The OpenID Connect id_token.
     *
     * @see http://tools.ietf.org/html/rfc6749#section-4
     * @ingroup oauth2_section_4
     */
    public function createAuthorizationCode($client_id, $user_id, $redirect_uri, $scope = null, $id_token = null)
    {
        $code = $this->generateAuthorizationCode();
        $this->storage->setAuthorizationCode($code, $client_id, $user_id, $redirect_uri, time() + $this->config['auth_code_lifetime'], $scope, $id_token);

        return $code;
    }
}
