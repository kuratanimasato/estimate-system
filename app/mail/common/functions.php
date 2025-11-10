<?php
define('ENC_KEY', 'your-secret-key-1234');
define('ENC_IV', substr(hash('sha256', 'iv1234567890'), 0, 16));

function encryptValue($value)
{
  return base64_encode(openssl_encrypt($value, 'AES-256-CTR', ENC_KEY, 0, ENC_IV));
}

function decryptValue($value)
{
  return openssl_decrypt(base64_decode($value), 'AES-256-CTR', ENC_KEY, 0, ENC_IV);
}