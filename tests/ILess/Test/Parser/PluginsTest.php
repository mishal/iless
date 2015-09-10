<?php

namespace ILess\Test\Parser;

use ILess\Context;
use ILess\Node;
use ILess\Node\CallNode;
use ILess\Node\RuleNode;
use ILess\Node\UrlNode;
use ILess\Parser;
use ILess\Plugin\PluginInterface;
use ILess\Plugin\PostProcessorInterface;
use ILess\Plugin\PreProcessorInterface;
use ILess\Visitor\Visitor;
use ILess\Visitor\VisitorArguments;
use ILess\Visitor\VisitorInterface;

class myTestPlugin implements PluginInterface
{
    public function install(Parser $parser)
    {
        $parser->getPluginManager()->addPreProcessor(new myTestPreProcessor());
    }
}

class myTest2Plugin implements PluginInterface
{
    public function install(Parser $parser)
    {
        $parser->getPluginManager()->addPostProcessor(new myTestPostProcessor());
    }
}

class myTest3Plugin implements PluginInterface
{

    public function install(Parser $parser)
    {
        $parser->getPluginManager()->addVisitor(new myTestVisitor());
    }

}

class myTestPreProcessor implements PreProcessorInterface
{
    public function process($inputString, array $extra)
    {
        return "/*! Bannerized by pre processor */\n\n".$inputString;
    }
}

class myTestPostProcessor implements PostProcessorInterface
{
    public function process($css, array $extra)
    {
        return $css."\n/* POST TOUCHED */";
    }
}

class ParamStringReplacementNode extends Node
{
    protected $type = 'ParamStringReplacementNode';

    public function compile(Context $context, $arguments = null, $important = null)
    {
        $quoted = $this->value->compile($context);
        if ($quoted->value && is_string($quoted->value)) {
            $quoted->value = preg_replace('/\?[^#]*/', '', $quoted->value);
        }

        return $quoted;
    }
}

class myTestVisitor extends Visitor
{
    protected $type = VisitorInterface::TYPE_PRE_COMPILE;
    protected $isReplacing = true;
    private $inRule = false;

    public function visitRule(RuleNode $node, VisitorArguments $arguments)
    {
        $this->inRule = true;

        return $node;
    }

    public function visitRuleOut(RuleNode $node, VisitorArguments $arguments)
    {
        $this->inRule = false;
    }

    public function visitUrl(UrlNode $node, VisitorArguments $arguments)
    {
        if (!$this->inRule) {
            return $node;
        }

        return new CallNode('data-uri', [
            new ParamStringReplacementNode($node->value),
        ], $node->index, $node->currentFileInfo);
    }
}

class PluginsTest extends \PHPUnit_Framework_TestCase
{

    public function testPreProcessingPlugin()
    {
        $parser = new Parser();
        $parser->getPluginManager()->addPlugin(new myTestPlugin());

        $parser->parseString('body { color: red; }');
        $css = $parser->getCSS();
        $expected = <<< CSS
/*! Bannerized by pre processor */
body {
  color: red;
}

CSS;
        $this->assertEquals($expected, $css, 'The pre processor did something');
    }

    public function testPostProcessingPlugin()
    {
        $parser = new Parser();
        $parser->getPluginManager()->addPlugin(new myTest2Plugin());
        $parser->parseString('body { color: red; }');
        $css = $parser->getCSS();
        $expected = <<< CSS
body {
  color: red;
}

/* POST TOUCHED */
CSS;
        $this->assertEquals($expected, $css, 'The post processor did something');
    }

    public function testVisitorPlugin()
    {
        $parser = new Parser();
        $parser->getPluginManager()->addPlugin(new myTest3Plugin());

        // we fake the path as base dir for the file
        $parser->parseString('body { background: url("data/image.svg"); }',
            __DIR__.'/_fixtures/less.js/string.less');

        $css = $parser->getCSS();
        $expected = <<< CSS
body {
  background: url("data:image/svg+xml,%3C%3Fxml%20version%3D%221.0%22%20encoding%3D%22UTF-8%22%20standalone%3D%22no%22%3F%3E%0A%3Csvg%20height%3D%22100%22%20width%3D%22100%22%3E%0A%20%20%3Ccircle%20cx%3D%2250%22%20cy%3D%2250%22%20r%3D%2240%22%20stroke%3D%22black%22%20stroke-width%3D%221%22%20fill%3D%22blue%22%20%2F%3E%0A%3C%2Fsvg%3E%0A");
}

CSS;

        $this->assertEquals($expected, $css, 'The visitor did something');
    }

}
