<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Plugin;

use ILess\Configurable;
use ILess\Parser;

/**
 * Plugin which can be configured.
 */
abstract class Plugin extends Configurable implements PluginInterface
{
    /**
     * Installs itself.
     *
     * @param Parser $parser
     */
    abstract public function install(Parser $parser);
}
