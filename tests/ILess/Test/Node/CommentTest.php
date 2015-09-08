<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Context;
use ILess\Node\CommentNode;
use ILess\Output\StandardOutput;

/**
 * Comment node tests
 *
 * @package ILess
 * @subpackage test
 * @covers Node_Comment
 * @group node
 */
class Test_Node_CommentTest extends Test_TestCase
{
    /**
     * @covers getType
     */
    public function testGetType()
    {
        $a = new CommentNode('my comment');
        $this->assertEquals('Comment', $a->getType());
    }

    /**
     * @covers generateCss
     */
    public function testGenerateCSS()
    {
        $env = new Context();

        $a = new CommentNode('my comment');
        $output = new StandardOutput();

        $a->generateCss($env, $output);
        $this->assertEquals($output->toString(), 'my comment');
    }

    public function testIsSilent()
    {
        $env = new Context();
        $a = new CommentNode('// This is a comment', true);
        $b = new CommentNode('/* This is a comment */');
        $c = new CommentNode('/*! This is a comment */');

        $this->assertTrue($a->isSilent($env));
        $this->assertFalse($b->isSilent($env));

        // when compression is used
        $env->compress = true;
        $this->assertTrue($b->isSilent($env));
        $this->assertFalse($c->isSilent($env));
    }

}
