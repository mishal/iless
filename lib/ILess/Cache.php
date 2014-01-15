<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Cache
 *
 * @package ILess
 * @subpackage cache
 */
abstract class ILess_Cache extends ILess_Configurable implements ILess_CacheInterface
{
    /**
     * Creates an instance of cache driver
     *
     * @param string $driver The driver name
     * @param array|mixed $options Array of options for the driver
     */
    public static function factory($driver, $options = array())
    {
        if (!class_exists($cacheClass = sprintf('ILess_Cache_%s', $driver))) {
            throw new InvalidArgumentException(sprintf('The cache driver "%s" does not exist.', $driver));
        }

        return new $cacheClass($options);
    }

    /**
     * Returns the configured lifetime
     *
     * @param integer $lifetime Lifetime in seconds
     * @return integer Lifetime in seconds
     */
    protected function getLifetime($lifetime)
    {
        return null === $lifetime ? $this->getOption('ttl', 0) : $lifetime;
    }

    /**
     * Serialized data to be stored in the cache
     *
     * @param mixed $data
     * @return string
     */
    protected function serialize($data)
    {
        return serialize($data);
    }

    /**
     * Unserialized data taken from the cache
     *
     * @param string $data
     * @return mixed
     */
    protected function unserialize($data)
    {
        return unserialize($data);
    }

}
