<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Cache;

use ILess\Configurable;
use ILess\Util\Serializer;
use InvalidArgumentException;

/**
 * Cache.
 */
abstract class Cache extends Configurable implements CacheInterface
{
    /**
     * Creates an instance of cache driver.
     *
     * @param string $driver The driver name
     * @param array|mixed $options Array of options for the driver
     */
    public static function factory($driver, $options = [])
    {
        if (!class_exists($cacheClass = sprintf('ILess\Cache\%s', $driver))) {
            throw new InvalidArgumentException(sprintf('The cache driver "%s" does not exist.', $driver));
        }

        return new $cacheClass($options);
    }

    /**
     * Returns the configured lifetime.
     *
     * @param int $lifetime Lifetime in seconds
     *
     * @return int Lifetime in seconds
     */
    protected function getLifetime($lifetime)
    {
        return null === $lifetime ? $this->getOption('ttl', 0) : $lifetime;
    }

    /**
     * Serialized data to be stored in the cache.
     *
     * @param mixed $data
     *
     * @return string
     */
    protected function serialize($data)
    {
        return Serializer::serialize($data);
    }

    /**
     * Unserialized data taken from the cache.
     *
     * @param string $data
     *
     * @return mixed
     */
    protected function unserialize($data)
    {
        return Serializer::unserialize($data);
    }
}
