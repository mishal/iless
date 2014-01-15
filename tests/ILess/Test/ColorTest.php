<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Color tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Color
 */
class ILess_Test_ColorTest extends ILess_Test_TestCase
{
    /**
     * @covers getAlpha
     */
    public function testAlpha()
    {
        $color = new ILess_Color();
        $this->assertEquals(1, $color->getAlpha());
    }

    /**
     * @covers toString
     */
    public function testToString()
    {
        $color = new ILess_Color('#ffeeaa');
        $this->assertEquals('#ffeeaa', $color->toString());

        // the format remains the same
        $color = new ILess_Color('#fea');
        $this->assertEquals('#ffeeaa', $color->toString());

        $color = new ILess_Color('#ff0000');
        $this->assertEquals('#ff0000', $color->toString());

        $this->assertEquals(255, $color->getRed());
        $this->assertEquals(0, $color->getGreen());
        $this->assertEquals(0, $color->getBlue());

        $color = new ILess_Color('#f60000');
        $this->assertEquals('#f60000', $color->toString());

        $this->assertEquals(246, $color->getRed());
        $this->assertEquals(0, $color->getGreen());
        $this->assertEquals(0, $color->getBlue());
    }

}
