<?php

require_once 'HTTP/OAuth2/Storage/ScopeInterface.php';

/**
 * Class to handle scope implementation logic
 *
 * @see OAuth2\Storage\ScopeInterface
 */
interface HTTP_OAuth2_ScopeInterface extends HTTP_OAuth2_Storage_ScopeStorageInterface
{
    /**
     * Check if everything in required scope is contained in available scope.
     *
     * @param $required_scope
     * A space-separated string of scopes.
     *
     * @return
     * TRUE if everything in required scope is contained in available scope,
     * and FALSE if it isn't.
     *
     * @see http://tools.ietf.org/html/rfc6749#section-7
     *
     * @ingroup oauth2_section_7
     */
    public function checkScope($required_scope, $available_scope);

    /**
     * Return scope info from request
     *
     * @param OAuth2\RequestInterface
     * Request object to check
     *
     * @return
     * string representation of requested scope
     */
    public function getScopeFromRequest(RequestInterface $request);
}
