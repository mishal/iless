<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Plugin;

/**
 * Pre processor interface.
 */
interface PreProcessorInterface
{
    /**
     * Pre process the CSS before passing to the parser.
     *
     * @param string $inputString
     * @param array $extra Extra information
     *
     * @return string
     */
    public function process($inputString, array $extra);
}
