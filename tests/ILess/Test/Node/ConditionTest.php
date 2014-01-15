<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Condition node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Node_Condition
 */
class ILess_Test_Node_ConditionTest extends ILess_Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $c = new ILess_Node_Condition('>', new ILess_Node_Anonymous(5), new ILess_Node_Anonymous(4));
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $a = new ILess_Node_Condition('>', new ILess_Node_Anonymous(5), new ILess_Node_Anonymous(4));
        $this->assertEquals('Condition', $a->getType());
    }

    /**
     * @covers compile
     */
    public function testCompile()
    {
        $env = new ILess_Environment();

        // equal
        $c = new ILess_Node_Condition('=', new ILess_Node_Anonymous(5), new ILess_Node_Anonymous(5));
        $result = $c->compile($env);
        $this->assertTrue($result);

        // equal - false condition
        $c = new ILess_Node_Condition('=', new ILess_Node_Anonymous(5), new ILess_Node_Anonymous(4));
        $result = $c->compile($env);
        $this->assertFalse($result);

        // greater than
        $c = new ILess_Node_Condition('>', new ILess_Node_Anonymous(5), new ILess_Node_Anonymous(4));
        $result = $c->compile($env);
        $this->assertTrue($result);

        // lower than
        $c = new ILess_Node_Condition('<', new ILess_Node_Anonymous(5), new ILess_Node_Anonymous(4));
        $result = $c->compile($env);
        $this->assertFalse($result);

        // lower or equal than
        $c = new ILess_Node_Condition('<=', new ILess_Node_Anonymous(5), new ILess_Node_Anonymous(5));
        $result = $c->compile($env);
        $this->assertTrue($result);

        // lower or equal than -> operator modified
        $c = new ILess_Node_Condition('=<', new ILess_Node_Anonymous(5), new ILess_Node_Anonymous(5));
        $result = $c->compile($env);
        $this->assertTrue($result);

        // greater or equal than
        $c = new ILess_Node_Condition('>=', new ILess_Node_Anonymous(5), new ILess_Node_Anonymous(5));
        $result = $c->compile($env);
        $this->assertTrue($result);

        // greater or equal than -> operator modified
        $c = new ILess_Node_Condition('=>', new ILess_Node_Anonymous(6), new ILess_Node_Anonymous(5));
        $result = $c->compile($env);
        $this->assertTrue($result);

        // greater or equal than -> false condition
        $c = new ILess_Node_Condition('=>', new ILess_Node_Anonymous(5), new ILess_Node_Anonymous(7));
        $result = $c->compile($env);
        $this->assertFalse($result);
    }

}
