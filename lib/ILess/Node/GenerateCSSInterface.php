<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Node;

use ILess\Context;
use ILess\Output\OutputInterface;

/**
 * Generates CSS interface.
 */
interface GenerateCSSInterface
{
    /**
     * Generate the CSS and put it in the output container.
     *
     * @param Context $context The context
     * @param OutputInterface $output The output
     */
    public function generateCSS(Context $context, OutputInterface $output);

    /**
     * Compiles the node to CSS.
     *
     * @param Context $context
     *
     * @return string
     */
    public function toCSS(Context $context);
}
