<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use ILess\Color;
use ILess\Node\ColorNode;
use ILess\Node\DimensionNode;
use ILess\Node\ValueNode;
use ILess\Node\UnitNode;
use ILess\Node\RuleNode;
use ILess\Variable;
use ILess\Node\QuotedNode;

/**
 * ILess\Variable tests
 *
 * @package ILess
 * @subpackage test
 * @covers Variable
 */
class Test_VariableTest extends Test_TestCase
{
    /**
     * @covers       toNode
     * @dataProvider getDataForTestCreateTest
     */
    public function testCreate($name, $value, $expectedObj)
    {
        $variable = Variable::create($name, $value);
        $node = $variable->toNode();

        // convert to node
        $this->assertEquals($node, $expectedObj);
    }

    public function getDataForTestCreateTest()
    {
        return [
            [
                'foo',
                '#ffffff',
                new RuleNode('@foo',
                    new ValueNode([new ColorNode(new Color('#ffffff'))])),
            ],
            [
                'angle',
                '-20rad',
                new RuleNode('@angle',
                    new ValueNode([
                        new DimensionNode('-20', new UnitNode(['rad'])),
                    ])),
            ],
            [
                'foobar',
                '12px',
                new RuleNode('@foobar', new ValueNode([
                        new DimensionNode('12', new UnitNode(['px'])),
                    ])
                ),
            ],
            [
                'myurl',
                '"http://example.com/image.jpg"',
                new RuleNode('@myurl', new ValueNode([
                    new QuotedNode(
                        '"http://example.com/image.jpg"', 'http://example.com/image.jpg'),
                ])),
            ],
            [
                'rgb',
                'rgb(46, 120, 176)',
                new RuleNode('@rgb', new ValueNode([new ColorNode([46, 120, 176])])),
            ],
            [
                'rgba',
                'rgba(46, 120, 176, 0.5)',
                new RuleNode('@rgba', new ValueNode([new ColorNode([46, 120, 176], 0.5)])),
            ]
        ];
    }

}
