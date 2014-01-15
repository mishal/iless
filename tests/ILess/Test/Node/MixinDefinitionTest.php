<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * MixinDefinition node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Node_MixinDefinition
 */
class ILess_Test_Node_MixinDefinitionTest extends ILess_Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $md = new ILess_Node_MixinDefinition('foobar');
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $md = new ILess_Node_MixinDefinition('foobar');
        $this->assertEquals('MixinDefinition', $md->getType());
    }

}
