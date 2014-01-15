<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class ILess_Test_SourceMap_Generator extends ILess_SourceMap_Generator
{
    public function generateJson()
    {
        return parent::generateJson();
    }
}

/**
 * @covers ILess_SourceMap_Generator
 */
class ILess_Test_SourceMap_GeneratorTest extends ILess_Test_TestCase
{
    /**
     * @covers generateJson
     */
    public function testBasic()
    {
        $g = new ILess_Test_SourceMap_Generator(new ILess_Node_Ruleset(array(), array()), array());
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
        $g = new ILess_Test_SourceMap_Generator(new ILess_Node_Ruleset(array(), array()), array());
    }

}
