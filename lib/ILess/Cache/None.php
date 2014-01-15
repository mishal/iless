<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Cache which does no storing at all
 *
 * @package ILess
 * @subpackage cache
 */
class ILess_Cache_None extends ILess_Cache
{
    /**
     * Constructor
     *
     */
    public function __construct()
    {
    }

    /**
     * @see ILess_CacheInterface::has
     */
    public function has($cacheKey)
    {
        return false;
    }

    /**
     * @see ILess_CacheInterface::get
     */
    public function get($cacheKey)
    {
    }

    /**
     * @see ILess_CacheInterface::set
     */
    public function set($cacheKey, $data, $ttl = null)
    {
    }

    /**
     * @see ILess_CacheInterface::remove
     */
    public function remove($cacheKey)
    {
    }

    /**
     * @see ILess_CacheInterface::clean
     */
    public function clean()
    {
    }

}
