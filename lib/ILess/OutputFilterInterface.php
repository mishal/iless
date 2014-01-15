<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Output filter interface
 *
 * @package ILess
 * @subpackage filter
 */
interface ILess_OutputFilterInterface
{
    /**
     * Filters the output
     *
     * @param string $output
     * @return string
     */
    public function filter($output);

}
