<?php declare(strict_types=1);

namespace FacturaScripts\Core\Lib;

use Exception;
use FacturaScripts\Core\KernelException;
use function openssl_decrypt;
use function openssl_encrypt;

class Encrypter
{
    public const DEFAULT_CIPHER = 'aes-128-cbc';

    /**
     * The supported cipher algorithms and their properties.
     *
     * @var array
     */
    private static $supportedCiphers = [
        'aes-128-cbc' => ['size' => 16, 'aead' => false],
        'aes-256-cbc' => ['size' => 32, 'aead' => false],
        'aes-128-gcm' => ['size' => 16, 'aead' => true],
        'aes-256-gcm' => ['size' => 32, 'aead' => true],
    ];
    /**
     * The encryption key.
     *
     * @var string
     */
    protected $key;
    /**
     * The previous / legacy encryption keys.
     *
     * @var array
     */
    protected $previousKeys = [];
    /**
     * The algorithm used for encryption.
     *
     * @var string
     */
    protected $cipher;

    /**
     * Create a new encrypter instance.
     *
     * @param string $key
     * @param string $cipher
     * @return void
     *
     * @throws KernelException
     */
    public function __construct($key, $cipher = self::DEFAULT_CIPHER)
    {
        $key = (string)$key;

        if (!static::supported($key, $cipher)) {
            $ciphers = implode(', ', array_keys(self::$supportedCiphers));

            throw new KernelException("DefaultError", "Unsupported cipher or incorrect key length. Supported ciphers are: {$ciphers}.");
        }

        $this->key = $key;
        $this->cipher = $cipher;
    }

    /**
     * Determine if the given key and cipher combination is valid.
     *
     * @param string $key
     * @param string $cipher
     * @return bool
     */
    public static function supported($key, $cipher)
    {
        if (!isset(self::$supportedCiphers[strtolower($cipher)])) {
            return false;
        }

        return mb_strlen($key, '8bit') === self::$supportedCiphers[strtolower($cipher)]['size'];
    }

    /**
     * Create a new encryption key for the given cipher.
     *
     * @param string $cipher
     * @return string
     *
     * @throws Exception
     */
    public static function generateKey($cipher = self::DEFAULT_CIPHER)
    {
        return random_bytes(self::$supportedCiphers[strtolower($cipher)]['size'] ?? 32);
    }

    /**
     * Encrypt a string without serialization.
     *
     * @param string $value
     * @return string
     *
     * @throws Exception
     */
    public function encryptString($value)
    {
        return $this->encrypt($value, false);
    }

    /**
     * Encrypt the given value.
     *
     * @param mixed $value
     * @param bool $serialize
     * @return string
     *
     * @throws Exception
     */
    public function encrypt($value, $serialize = true)
    {
        $iv = random_bytes(openssl_cipher_iv_length(strtolower($this->cipher)));

        $value = openssl_encrypt(
            $serialize ? serialize($value) : $value,
            strtolower($this->cipher),
            $this->key,
            0,
            $iv,
            $tag
        );

        if ($value === false) {
            throw new KernelException('DefaultError', 'Could not encrypt the data.');
        }

        $iv = base64_encode($iv);
        $tag = base64_encode($tag ?? '');

        $mac = self::$supportedCiphers[strtolower($this->cipher)]['aead']
            ? '' // For AEAD-algorithms, the tag / MAC is returned by openssl_encrypt...
            : $this->hash($iv, $value, $this->key);

        $json = json_encode(compact('iv', 'value', 'mac', 'tag'), JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new KernelException('DefaultError', 'Could not encrypt the data.');
        }

        return base64_encode($json);
    }

    /**
     * Create a MAC for the given value.
     *
     * @param string $iv
     * @param mixed $value
     * @param string $key
     * @return string
     */
    protected function hash($iv, $value, $key)
    {
        return hash_hmac('sha256', $iv . $value, $key);
    }

    /**
     * Decrypt the given string without unserialization.
     *
     * @param string $payload
     * @return string
     *
     * @throws KernelException
     */
    public function decryptString($payload)
    {
        return $this->decrypt($payload, false);
    }

    /**
     * Decrypt the given value.
     *
     * @param string $payload
     * @param bool $unserialize
     * @return mixed
     *
     * @throws KernelException
     */
    public function decrypt($payload, $unserialize = true)
    {
        $payload = $this->getJsonPayload($payload);

        $iv = base64_decode($payload['iv']);

        $this->ensureTagIsValid(
            $tag = empty($payload['tag']) ? null : base64_decode($payload['tag'])
        );

        // Here we will decrypt the value. If we are able to successfully decrypt it
        // we will then unserialize it and return it out to the caller. If we are
        // unable to decrypt this value we will throw out an exception message.
        foreach ($this->getAllKeys() as $key) {
            $decrypted = openssl_decrypt(
                $payload['value'],
                strtolower($this->cipher),
                $key,
                0,
                $iv,
                $tag ?? ''
            );

            if ($decrypted !== false) {
                break;
            }
        }

        if ($decrypted === false) {
            throw new KernelException('DefaultError', 'Could not decrypt the data.');
        }

        return $unserialize ? unserialize($decrypted) : $decrypted;
    }

    /**
     * Get the JSON array from the given payload.
     *
     * @param string $payload
     * @return array
     *
     * @throws KernelException
     */
    protected function getJsonPayload($payload)
    {
        if (!is_string($payload)) {
            throw new KernelException('DefaultError', 'The payload is invalid.');
        }

        $payload = json_decode(base64_decode($payload), true);

        // If the payload is not valid JSON or does not have the proper keys set we will
        // assume it is invalid and bail out of the routine since we will not be able
        // to decrypt the given value. We'll also check the MAC for this encryption.
        if (!$this->validPayload($payload)) {
            throw new KernelException('DefaultError', 'The payload is invalid.');
        }

        if (!self::$supportedCiphers[strtolower($this->cipher)]['aead'] && !$this->validMac($payload)) {
            throw new KernelException('DefaultError', 'The MAC is invalid.');
        }

        return $payload;
    }

    /**
     * Verify that the encryption payload is valid.
     *
     * @param mixed $payload
     * @return bool
     */
    protected function validPayload($payload)
    {
        if (!is_array($payload)) {
            return false;
        }

        foreach (['iv', 'value', 'mac'] as $item) {
            if (!isset($payload[$item]) || !is_string($payload[$item])) {
                return false;
            }
        }

        if (isset($payload['tag']) && !is_string($payload['tag'])) {
            return false;
        }

        return strlen(base64_decode($payload['iv'], true)) === openssl_cipher_iv_length(strtolower($this->cipher));
    }

    /**
     * Determine if the MAC for the given payload is valid.
     *
     * @param array $payload
     * @return bool
     */
    protected function validMac(array $payload)
    {
        foreach ($this->getAllKeys() as $key) {
            $valid = hash_equals(
                $this->hash($payload['iv'], $payload['value'], $key),
                $payload['mac']
            );

            if ($valid === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the current encryption key and all previous encryption keys.
     *
     * @return array
     */
    public function getAllKeys()
    {
        return array_merge([$this->key], $this->previousKeys);
    }

    /**
     * Ensure the given tag is a valid tag given the selected cipher.
     *
     * @param string $tag
     * @return void
     *
     * @throws KernelException
     */
    protected function ensureTagIsValid($tag):void
    {
        if (self::$supportedCiphers[strtolower($this->cipher)]['aead'] && strlen($tag) !== 16) {
            throw new KernelException('DefaultError', 'Could not decrypt the data.');
        }

        if (!self::$supportedCiphers[strtolower($this->cipher)]['aead'] && is_string($tag)) {
            throw new KernelException('DefaultError', 'Unable to use tag because the cipher algorithm does not support AEAD.');
        }
    }

    /**
     * Get the encryption key that the encrypter is currently using.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Get the previous encryption keys.
     *
     * @return array
     */
    public function getPreviousKeys()
    {
        return $this->previousKeys;
    }

    /**
     * Set the previous / legacy encryption keys that should be utilized if decryption fails.
     *
     * @param array $keys
     * @return $this
     * @throws KernelException
     */
    public function previousKeys(array $keys)
    {
        foreach ($keys as $key) {
            if (!static::supported($key, $this->cipher)) {
                $ciphers = implode(', ', array_keys(self::$supportedCiphers));

                throw new KernelException("DefaultError", "Unsupported cipher or incorrect key length. Supported ciphers are: {$ciphers}.");
            }
        }

        $this->previousKeys = $keys;

        return $this;
    }
}
