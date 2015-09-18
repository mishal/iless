<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Node\RulesetNode;
use ILess\SourceMap\Generator;

/**
 * @group sourceMap
 */
class Test_SourceMap_Generator extends Generator
{
    public function generateJson()
    {
        return parent::generateJson();
    }
}

/**
 * @covers SourceMap_Generator
 */
class Test_SourceMap_GeneratorTest extends Test_TestCase
{
    /**
     * @covers generateJson
     */
    public function testBasic()
    {
        $g = new Test_SourceMap_Generator(new RulesetNode([], []), []);
        $this->assertEquals(
            '{"version":3,"file":null,"sourceRoot":"","sources":[],"names":[],"mappings":""}',
            $g->generateJson()
        );
    }

    /**
     * @covers generateMappings
     */
    public function testGenerateMappings()
    {
        $g = new Test_SourceMap_Generator(new RulesetNode([], []), []);
    }

}
