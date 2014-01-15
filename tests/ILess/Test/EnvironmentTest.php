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

}
