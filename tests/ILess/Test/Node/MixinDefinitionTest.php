<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Node\MixinDefinitionNode;

/**
 * MixinDefinition node tests
 *
 * @package ILess
 * @subpackage test
 * @covers Node_MixinDefinition
 * @group node
 */
class Test_Node_MixinDefinitionTest extends Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $md = new MixinDefinitionNode('foobar');
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $md = new MixinDefinitionNode('foobar');
        $this->assertEquals('MixinDefinition', $md->getType());
    }

}
