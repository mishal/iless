<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Output filter
 *
 * @package ILess
 * @subpackage output
 */
class ILess_OutputFilter_GzCompress extends ILess_OutputFilter
{
    /**
     * Array of default options
     *
     * @var array
     */
    protected $defaultOptions = array(
        'compression_level' => 9
    );

    /**
     * @see ILess_OutputFilterInterface
     */
    public function filter($output)
    {
        return gzcompress($output, $this->getOption('compression_level'));
    }

}
