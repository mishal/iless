<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Environment tests
 *
 * @package ILess
 * @subpackage test
 */
class ILess_Test_EnvironmentTest extends ILess_Test_TestCase
{
    /**
     * @covers createCopy
     */
    public function testNormalizePath()
    {
        $env = new ILess_Environment();
        $copy = ILess_Environment::createCopy($env, array(1));
        $this->assertInstanceOf('ILess_Environment', $copy);
        $this->assertEquals($copy->frames, array(1));
    }

    /**
     * @covers __construct
     */
    public function testOptions()
    {
        $env = new ILess_Environment(array(
            'source_map' => true,
            'strict_units' => 1,
            'tab_level' => 2,
            'strictMath' => true,
        ));

        $this->assertTrue($env->sourceMap);
        $this->assertTrue($env->strictUnits);
        $this->assertTrue($env->strictMath);
        $this->assertEquals(2, $env->tabLevel);
    }

    /**
     * @covers __construct
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid options "foo, foo_bar" given.
     */
    public function testInvalidOptions()
    {
        new ILess_Environment(array(
            'foo' => true,
            'foo_bar' => 1,
        ));
    }
}
