<?php


 *
 */
interface TokenControllerInterface
{
    /**
     * handleTokenRequest
     *
     * @param $request
     * OAuth2\RequestInterface - The current http request
     * @param $response
     * OAuth2\ResponseInterface - An instance of OAuth2\ResponseInterface to contain the response data
     *
     */
    public function handleTokenRequest(RequestInterface $request, ResponseInterface $response);

    public function grantAccessToken(RequestInterface $request, ResponseInterface $response);
}
