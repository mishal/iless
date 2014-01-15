<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Url node tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Node_Url
 */
class ILess_Test_Node_UrlTest extends ILess_Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $d = new ILess_Node_Url(new ILess_Node_Quoted('"http://foobar.com/less.css"', 'http://foobar.com/less.css'));
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $d = new ILess_Node_Url(new ILess_Node_Quoted('"http://foobar.com/less.css"', 'http://foobar.com/less.css'));
        $this->assertEquals('Url', $d->getType());
    }

}
