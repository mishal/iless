<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Node\UrlNode;
use ILess\Node\QuotedNode;

/**
 * Url node tests
 *
 * @package ILess
 * @subpackage test
 * @covers Node_Url
 * @group node
 */
class Test_Node_UrlTest extends Test_TestCase
{
    /**
     * @covers __constructor
     */
    public function testConstructor()
    {
        $d = new UrlNode(new QuotedNode('"http://foobar.com/less.css"', 'http://foobar.com/less.css'));
    }

    /**
     * @covers getType
     */
    public function testGetType()
    {
        $d = new UrlNode(new QuotedNode('"http://foobar.com/less.css"', 'http://foobar.com/less.css'));
        $this->assertEquals('Url', $d->getType());
    }

}
