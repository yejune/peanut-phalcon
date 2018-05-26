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
 * $crypt = new \Peanut\Phalcon\Crypt();
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
    public function getHashAlgo()
    {
        return $this->_hashAlgo;
    }
    public function getAvailableHashAlgos()
    {
        return hash_hmac_algos();
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
        $mode   = strtolower(substr($cipher, strrpos($cipher, '-') - strlen($cipher)));
        if (!in_array($cipher, $this->getAvailableCiphers())) {
            throw new Exception('Cipher algorithm is unknown');
        }

        $ivSize = openssl_cipher_iv_length($cipher);
        if ($ivSize > 0) {
            $blockSize = $ivSize;
        } else {
            $blockSize = openssl_cipher_iv_length(str_ireplace('-'.$mode, '', $cipher));
        }

        $iv          = openssl_random_pseudo_bytes($ivSize);
        $paddingType = $this->_padding;

        if ($paddingType != 0 && ($mode == 'cbc' || $mode == 'ecb')) {
            $padded = $this->_cryptPadText($plaintext, $mode, $blockSize, $paddingType);
        } else {
            $padded = $plaintext;
        }

        $hashAlgo   = $this->getHashAlgo();

        if (!in_array($hashAlgo, $this->getAvailableHashAlgos())) {
            throw new Exception('Hash algorithm is unknown');
        }

        return $iv.hash_hmac($hashAlgo, $padded, $encryptKey, true).openssl_encrypt($padded, $cipher, $encryptKey, \OPENSSL_RAW_DATA, $iv);
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
        $mode   = strtolower(substr($cipher, strrpos($cipher, '-') - strlen($cipher)));

        if (!in_array($cipher, $this->getAvailableCiphers())) {
            throw new Exception('Cipher algorithm is unknown');
        }

        $ivSize = openssl_cipher_iv_length($cipher);
        if ($ivSize > 0) {
            $blockSize = $ivSize;
        } else {
            $blockSize = openssl_cipher_iv_length(str_ireplace('-'.$mode, '', $cipher));
        }

        $hashAlgo   = $this->getHashAlgo();
        $hashLength = strlen(hash($hashAlgo, '', true));
        $iv         = substr($ivHashCiphertext, 0, $blockSize);
        $hash       = substr($ivHashCiphertext, $blockSize, $hashLength);
        $ciphertext = substr($ivHashCiphertext, $blockSize + $hashLength);

        if (!in_array($hashAlgo, $this->getAvailableHashAlgos())) {
            throw new Exception('Hash algorithm is unknown');
        }

        $decrypted = openssl_decrypt($ciphertext, $cipher, $decryptKey, \OPENSSL_RAW_DATA, $iv);

        $paddingType = $this->_padding;

        if ($mode == 'cbc' || $mode == 'ecb') {
            $result = $this->_cryptUnpadText($decrypted, $mode, $blockSize, $paddingType);
        } else {
            $result = $decrypted;
        }

        if (false === hash_equals(hash_hmac($hashAlgo, $result, $decryptKey, true), $hash)) {
            throw new Exception('Hash does not match');
        }

        return $result;
    }
}
