<?php

use ILess\Parser;
use ILess\Plugin\PluginInterface;
use ILess\Plugin\PostProcessorInterface;
use ILess\Plugin\PreProcessorInterface;
use ILess\PluginManager;
use ILess\Visitor\Visitor;

class myTestPlugin implements PluginInterface
{
    public function install(Parser $parser)
    {
    }
}

class myTestVisitor extends Visitor
{
    public function run($root)
    {
    }
}

class myTestPreProcessor implements PreProcessorInterface
{
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function process($inputString, array $extra)
    {
        return $inputString.'-pre-processed-by '.$this->name;
    }
}

class myTestPostProcessor implements PostProcessorInterface
{
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function process($css, array $extra)
    {
        return $css.'-pre-processed-by '.$this->name;
    }
}

class PluginManagerTest extends PHPUnit_Framework_TestCase
{
    protected function getParserMock()
    {
        $mock = $this->getMockBuilder('ILess\Parser')
            ->disableOriginalConstructor()
            ->getMock();

        return $mock;
    }

    public function testPlugins()
    {
        $m = new PluginManager($this->getParserMock());

        $r = $m->addPlugin($plugin = new myTestPlugin());

        $this->assertInstanceOf('ILess\PluginManager', $r, 'fluent interface works');

        $plugins = $m->getPlugins();

        $this->assertEquals([$plugin], $plugins, 'getPlugins returns an array of plugins');

        $m->addPlugins([$plugin]);

        $plugins = $m->getPlugins();

        $this->assertEquals([$plugin, $plugin], $plugins, 'getPlugins returns an array of plugins');
    }

    public function testVisitors()
    {
        $m = new PluginManager($this->getParserMock());

        $r = $m->addVisitor($visitor = new myTestVisitor());

        $this->assertInstanceOf('ILess\PluginManager', $r, 'fluent interface works');

        $visitors = $m->getVisitors();

        $this->assertEquals([$visitor], $visitors, 'getVisitors returns an array of visitors');

        $m->addVisitors([$visitor]);

        $visitors = $m->getVisitors();

        $this->assertEquals([$visitor, $visitor], $visitors, 'getVisitors returns an array of visitors');
    }

    public function testPreprocessors()
    {
        $m = new PluginManager($this->getParserMock());

        $r = $m->addPreProcessor($preprocessor1 = new myTestPreProcessor('first'));

        $this->assertInstanceOf('ILess\PluginManager', $r, 'fluent interface works');

        $preprocessors = $m->getPreProcessors();

        $this->assertEquals([$preprocessor1], $preprocessors,
            'getPreProcessors returns an array of preprocessors');

        // add second but with higher priority
        $m->addPreProcessor($preprocessor2 = new myTestPreProcessor('second'), 200);

        $preprocessors = $m->getPreProcessors();

        $this->assertEquals([$preprocessor2, $preprocessor1], $preprocessors,
            'getPreProcessors returns an array of preprocessors');
    }

    public function testPostprocessors()
    {
        $m = new PluginManager($this->getParserMock());

        $r = $m->addPostProcessor($postprocessor1 = new myTestPostProcessor('first'));

        $this->assertInstanceOf('ILess\PluginManager', $r, 'fluent interface works');

        $postprocessors = $m->getPostProcessors();

        $this->assertEquals([$postprocessor1], $postprocessors,
            'getPostProcessors returns an array of postprocessors');

        // add second but with higher priority
        $m->addPostProcessor($postprocessor2 = new myTestPostProcessor('second'), 200);

        $postprocessors = $m->getPostProcessors();

        $this->assertEquals([$postprocessor2, $postprocessor1], $postprocessors,
            'getPostProcessors returns an array of postprocessors');
    }

}
