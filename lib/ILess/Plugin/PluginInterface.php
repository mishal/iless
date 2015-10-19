<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Plugin;

use ILess\Parser;

/**
 * Plugin interface.
 */
interface PluginInterface
{
    /**
     * Installs itself.
     *
     * @param Parser $parser
     */
    public function install(Parser $parser);
}
