<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class ILess_SourceMap_TestGenerator extends ILess_SourceMap_Generator
{
  public function generateJson()
  {
    return parent::generateJson();
  }
}

/**
 * @covers ILess_SourceMap_Generator
 */
class ILess_SourceMap_Generator_Test extends ILess_TestCase
{
  /**
   * @covers generateJson
   */
  public function testBasic()
  {
    $g = new ILess_SourceMap_TestGenerator(new ILess_Node_Ruleset(array(), array()), array());
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
    $g = new ILess_SourceMap_TestGenerator(new ILess_Node_Ruleset(array(), array()), array());
  }

}
