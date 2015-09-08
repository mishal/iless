<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Context;
use ILess\Node\AnonymousNode;
use ILess\Node\ConditionNode;
use ILess\Node\DimensionNode;

/**
 * Condition node tests
 *
 * @package ILess
 * @subpackage test
 * @covers Node_Condition
 */
class Test_Node_ConditionTest extends Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $c = new ConditionNode('>', new AnonymousNode(5), new AnonymousNode(4));
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $a = new ConditionNode('>', new AnonymousNode(5), new AnonymousNode(4));
        $this->assertEquals('Condition', $a->getType());
    }

    /**
     * @covers compile
     */
    public function testCompile()
    {
        $env = new Context();

        // equal - false condition
        $c = new ConditionNode('=', new AnonymousNode(5), new AnonymousNode(4));
        $result = $c->compile($env);
        $this->assertFalse($result);

        // equal
        $c = new ConditionNode('=', new AnonymousNode(5), new AnonymousNode(5));
        $result = $c->compile($env);
        $this->assertTrue($result);

        // greater than
        $c = new ConditionNode('>', new DimensionNode(5), new DimensionNode(4));
        $result = $c->compile($env);
        $this->assertTrue($result);

        // lower than
        $c = new ConditionNode('<', new DimensionNode(5), new DimensionNode(4));
        $result = $c->compile($env);
        $this->assertFalse($result);

        // lower or equal than
        $c = new ConditionNode('<=', new DimensionNode(5), new DimensionNode(5));
        $result = $c->compile($env);
        $this->assertTrue($result);

        // lower or equal than -> operator modified
        $c = new ConditionNode('=<', new DimensionNode(5), new DimensionNode(5));
        $result = $c->compile($env);
        $this->assertTrue($result);

        // greater or equal than
        $c = new ConditionNode('>=', new DimensionNode(5), new DimensionNode(5));
        $result = $c->compile($env);
        $this->assertTrue($result);

        // greater or equal than -> operator modified
        $c = new ConditionNode('=>', new DimensionNode(6), new DimensionNode(5));
        $result = $c->compile($env);
        $this->assertTrue($result);

        // greater or equal than -> false condition
        $c = new ConditionNode('=>', new DimensionNode(5), new DimensionNode(7));
        $result = $c->compile($env);
        $this->assertFalse($result);
    }

}
