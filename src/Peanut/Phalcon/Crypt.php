<?php
namespace Peanut\Phalcon;

use Phalcon\CryptInterface;
use Phalcon\Crypt\Exception;

/**
 * Phalcon\Crypt
 *
 * Provides encryption facilities to phalcon applications
 *
 *<code>
 * $crypt = new \Phalcon\Crypt();
 *
 * $key  = "le password";
 * $text = "This is a secret text";
 *
 * $encrypted = $crypt->encrypt($text, $key);
 *
 * echo $crypt->decrypt($encrypted, $key);
 *</code>
 */
class Crypt extends \Phalcon\Crypt
{
    protected $_key      = '';
    protected $_cipher   = 'aes-256-cfb';
    protected $_hashAlgo = 'sha256';

    public function setAlgo($algo) : Crypt
    {
        $this->_hashAlgo = $algo;

        return $this;
    }
    public function getAlgo()
    {
        return $this->_hashAlgo;
    }
    public function getAvailableHashAlgos()
    {
        return hash_algos();
    }
    public function encrypt($plaintext, $key = null)
    {
        if (false === function_exists('openssl_cipher_iv_length')) {
            throw new Exception('openssl extension is required');
        }

        if (null === $key) {
            $encryptKey = $this->getKey();
        } else {
            $encryptKey = $key;
        }

        if (!$encryptKey) {
            throw new Exception('Encryption key cannot be empty');
        }

        $cipher = $this->getCipher();

        if (!in_array($cipher, $this->getAvailableCiphers())) {
            throw new Exception('Cipher algorithm is unknown');
        }

        $hashAlgo   = $this->getAlgo();

        if (!in_array($hashAlgo, $this->getAvailableHashAlgos())) {
            throw new Exception('Hash algorithm is unknown');
        }

        $key        = hash($hashAlgo, $encryptKey, true);
        $iv         = openssl_random_pseudo_bytes(16);
        $ciphertext = openssl_encrypt($plaintext, $cipher, $key, \OPENSSL_RAW_DATA, $iv);
        $hash       = hash_hmac($hashAlgo, $ciphertext, $key, true);

        return $iv.$hash.$ciphertext;
    }

    public function decrypt($ivHashCiphertext, $key = null)
    {
        if (false === function_exists('openssl_cipher_iv_length')) {
            throw new Exception('openssl extension is required');
        }

        if (null === $key) {
            $decryptKey = $this->getKey();
        } else {
            $decryptKey = $key;
        }

        if (!$decryptKey) {
            throw new Exception('Decryption key cannot be empty');
        }

        $cipher = $this->getCipher();

        if (!in_array($cipher, $this->getAvailableCiphers())) {
            throw new Exception('Cipher algorithm is unknown');
        }

        $hashAlgo   = $this->getAlgo();

        if (!in_array($hashAlgo, $this->getAvailableHashAlgos())) {
            throw new Exception('Hash algorithm is unknown');
        }

        $key        = hash($hashAlgo, $decryptKey, true);
        $iv         = substr($ivHashCiphertext, 0, 16);
        $hash       = substr($ivHashCiphertext, 16, 32);
        $ciphertext = substr($ivHashCiphertext, 48);

        if (hash_hmac($hashAlgo, $ciphertext, $key, true) !== $hash) {
            return null;
        }

        return openssl_decrypt($ciphertext, $cipher, $key, \OPENSSL_RAW_DATA, $iv);
    }
}
