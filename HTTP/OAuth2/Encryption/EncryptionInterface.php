<?php

interface HTTP_OAuth2_Encryption_EncryptionInterface
{
    public function encode($payload, $key, $algorithm = null);
    public function decode($payload, $key, $algorithm = null);
    public function urlSafeB64Encode($data);
    public function urlSafeB64Decode($b64);
}
