<?php



interface ResponseTypeInterface
{
    public function getAuthorizeResponse($params, $user_id = null);
}
