<?php

namespace FacturaScripts\Core\Base;

/**
 * CacheItemPoolInterface generates CacheItemInterface objects.
 */
class Cache {

    /**
     * Configuration
     *
     * @access private
     */
    private static $config;

    /**
     * Constructor por defecto
     */
    public function __construct() {
        self::$config = array(
            'cache_path' => 'tmp/' . FS_TMP_NAME . 'cache',
            'expires' => 180,
        );

        if (!file_exists(self::$config['cache_path'])) {
            mkdir(self::$config['cache_path']);
        }
        if (!file_exists(self::$config['cache_path'] . '/deferred')) {
            mkdir(self::$config['cache_path'] . '/deferred');
        }
    }

    /**
     * Returns a Cache Item representing the specified key.
     *
     * This method must always return a CacheItemInterface object, even in case of
     * a cache miss. It MUST NOT return null.
     *
     * @param string $key
     *   The key for which to return the corresponding Cache Item.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return CacheItemInterface
     *   The corresponding Cache Item.
     */
    public function getItem($key, $raw = false, $custom_time = NULL) {
        if (!$this->file_expired($file = self::$config['cache_path'] . '/' . md5($key) . '.php', $custom_time)) {
            $content = file_get_contents($file);
            return $raw ? $content : unserialize($content);
        }

        return NULL;
    }

    /**
     * Returns a traversable set of cache items.
     *
     * @param string[] $keys
     *   An indexed array of keys of items to retrieve.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return array|\Traversable
     *   A traversable collection of Cache Items keyed by the cache keys of
     *   each item. A Cache item will be returned for each key, even if that
     *   key is not found. However, if no keys are specified then an empty
     *   traversable MUST be returned instead.
     */
    public function getItems(array $keys = array()) {
        $items = array();
        foreach ($keys as $key) {
            $items['$key'] = $this->getItem($key);
        }
        return $items[];
    }

    /**
     * Confirms if the cache contains specified cache item.
     *
     * Note: This method MAY avoid retrieving the cached value for performance reasons.
     * This could result in a race condition with CacheItemInterface::get(). To avoid
     * such situation use CacheItemInterface::isHit() instead.
     *
     * @param string $key
     *   The key for which to check existence.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if item exists in the cache, false otherwise.
     */
    public function hasItem($key) {
        $file = self::$config['cache_path'] . '/' . md5($key) . '.php';
        $done = TRUE;
        if (file_exists($file)) {
            $done = (time() > (filemtime($file) + 60 * ($time ? $time : self::$config['expires'])));
        }

        return $done;
    }

    /**
     * Deletes all items in the pool.
     *
     * @return bool
     *   True if the pool was successfully cleared. False if there was an error.
     */
    public function clear() {
        $cache_files = glob(self::$config['cache_path'] . '/*.php', GLOB_NOSORT);
        foreach ($cache_files as $file) {
            unlink($file);
        }
        return TRUE;
    }

    /**
     * Removes the item from the pool.
     *
     * @param string $key
     *   The key to delete.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully removed. False if there was an error.
     */
    public function deleteItem($key) {
        $done = TRUE;
        $ruta = self::$config['cache_path'] . '/' . md5($key) . '.php';
        if (file_exists($ruta)) {
            $done = unlink($ruta);
        }

        return $done;
    }

    /**
     * Removes multiple items from the pool.
     *
     * @param string[] $keys
     *   An array of keys that should be removed from the pool.

     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the items were successfully removed. False if there was an error.
     */
    public function deleteItems(array $keys) {
        foreach ($keys as $key) {
            $this->deleteItem($key);
        }
    }

    /**
     * Persists a cache item immediately.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   True if the item was successfully persisted. False if there was an error.
     */
    public function save(CacheItem $item) {
        
        $dest_file_name = self::$config['cache_path'] . '/' . md5($item->getKey()) . '.php';
        /** Use a unique temporary filename to make writes atomic with rewrite */
        $temp_file_name = str_replace(".php", uniqid("-", true) . ".php", $dest_file_name);
        $ret = @file_put_contents($temp_file_name, $raw ? $content : serialize($content));
        if ($ret !== FALSE) {
            return @rename($temp_file_name, $dest_file_name);
        }
        unlink($temp_file_name);
        return false;
    }

    /**
     * Sets a cache item to be persisted later.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   False if the item could not be queued or if a commit was attempted and failed. True otherwise.
     */
    public function saveDeferred(CacheItem $item) {
        $dest_file_name = self::$config['cache_path'] . '/deferred/' . md5($item->getKey()) . '.php';
        /** Use a unique temporary filename to make writes atomic with rewrite */
        $temp_file_name = str_replace(".php", uniqid("-", true) . ".php", $dest_file_name);
        $ret = @file_put_contents($temp_file_name, $raw ? $content : serialize($content));
        if ($ret !== FALSE) {
            return @rename($temp_file_name, $dest_file_name);
        }
        unlink($temp_file_name);
        return false;
    }

    /**
     * Persists any deferred cache items.
     *
     * @return bool
     *   True if all not-yet-saved items were successfully saved or there were none. False otherwise.
     */
    public function commit() {
        $deferred_files = self::$config['cache_path'] . '/deferred';
        $cache_path = self::$config['cache_path'];

        $dir = opendir($deferred_files);
        while (false !== ( $file = readdir($dir))) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if (is_dir($deferred_files . '/' . $file)) {
                    recurse_copy($deferred_files . '/' . $file, $cache_path . '/' . $file);
                } else {
                    copy($deferred_files . '/' . $file, $cache_path . '/' . $file);
                    unlink($deferred_files . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

}
