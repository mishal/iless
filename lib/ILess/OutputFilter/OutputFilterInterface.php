<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\OutputFilter;

/**
 * Output filter interface.
 *
 * @deprecated
 */
interface OutputFilterInterface
{
    /**
     * Filters the output.
     *
     * @param string $output
     *
     * @return string
     */
    public function filter($output);
}
