<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Media node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Node_Media
 */
class ILess_Test_Node_MediaTest extends ILess_Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $d = new ILess_Node_Media(array('black'));
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $d = new ILess_Node_Media(array('black'));
        $this->assertEquals('Media', $d->getType());
    }

    public function testCompile()
    {
        $env = new ILess_Environment();
        $d = new ILess_Node_Keyword('black');
        $result = $d->compile($env);
        $this->assertInstanceOf('ILess_Node_Keyword', $result);
    }

}
