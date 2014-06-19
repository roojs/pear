<?php

require_once 'HTTP/OAuth2/Storage/AuthorizationCodeInterface.php';
/**
 * Implement this interface to specify where the OAuth2 Server
 * should get/save authorization codes for the "Authorization Code"
 * grant type
 *
 * @author Brent Shaffer <bshafs at gmail dot com>
 */
interface HTTP_OAuth2_OpenID_Storage_AuthorizationCodeInterface extends HTTP_OAuth2_Storage_AuthorizationCodeInterface
{
    /**
     * Take the provided authorization code values and store them somewhere.
     *
     * This function should be the storage counterpart to getAuthCode().
     *
     * If storage fails for some reason, we're not currently checking for
     * any sort of success/failure, so you should bail out of the script
     * and provide a descriptive fail message.
     *
     * Required for OAuth2::GRANT_TYPE_AUTH_CODE.
     *
     * @param $code
     * Authorization code to be stored.
     * @param $client_id
     * Client identifier to be stored.
     * @param $user_id
     * User identifier to be stored.
     * @param string $redirect_uri
     * Redirect URI(s) to be stored in a space-separated string.
     * @param int $expires
     * Expiration to be stored as a Unix timestamp.
     * @param string $scope
     * (optional) Scopes to be stored in space-separated string.
     * @param string $id_token
     * (optional) The OpenID Connect id_token.
     *
     * @ingroup oauth2_section_4
     */
    public function setAuthorizationCode($code, $client_id, $user_id, $redirect_uri, $expires, $scope = null, $id_token = null);
}
