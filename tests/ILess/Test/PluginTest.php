<?php

namespace ILess\Test;

use ILess\Parser;
use ILess\Plugin\Plugin;

class myPlugin extends Plugin
{

    /**
     * Installs itself
     *
     * @param Parser $parser
     */
    public function install(Parser $parser)
    {
    }

}

class PluginTest extends \PHPUnit_Framework_TestCase
{
    public function testApi()
    {
        $p = new myPlugin([
            'foo' => 'bar',
        ]);

        $this->assertEquals($p->getOption('foo'), 'bar', 'The plugin is configurable');
    }
}
