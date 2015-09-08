<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use ILess\Color;

/**
 * Color tests
 *
 * @package ILess
 * @subpackage test
 * @covers Color
 */
class Test_ColorTest extends Test_TestCase
{
    /**
     * @covers getAlpha
     */
    public function testAlpha()
    {
        $color = new Color();
        $this->assertEquals(1, $color->getAlpha());
    }

    /**
     * @covers toString
     */
    public function testToString()
    {
        $color = new Color('#ffeeaa');
        $this->assertEquals('#ffeeaa', $color->toString());

        // the format remains the same
        $color = new Color('#fea');
        $this->assertEquals('#ffeeaa', $color->toString());

        $color = new Color('#ff0000');
        $this->assertEquals('#ff0000', $color->toString());

        $this->assertEquals(255, $color->getRed());
        $this->assertEquals(0, $color->getGreen());
        $this->assertEquals(0, $color->getBlue());

        $color = new Color('#f60000');
        $this->assertEquals('#f60000', $color->toString());

        $this->assertEquals(246, $color->getRed());
        $this->assertEquals(0, $color->getGreen());
        $this->assertEquals(0, $color->getBlue());
    }

}
