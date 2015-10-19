<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\OutputFilter;

/**
 * GzCompress filter.
 *
 * @deprecated
 */
class GzCompressOutputFilter extends OutputFilter
{
    /**
     * Array of default options.
     *
     * @var array
     */
    protected $defaultOptions = [
        'compression_level' => 9,
    ];

    /**
     * @see OutputFilterInterface
     */
    public function filter($output)
    {
        return gzcompress($output, $this->getOption('compression_level'));
    }
}
