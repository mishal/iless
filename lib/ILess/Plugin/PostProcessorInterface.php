<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Plugin;

/**
 * Post processor interface.
 */
interface PostProcessorInterface
{
    /**
     * Post process the generated CSS.
     *
     * @param string $css
     * @param array $extra Extra information
     *
     * @return string
     */
    public function process($css, array $extra);
}
