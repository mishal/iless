<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Issue #31 test
 *
 * @package ILess
 * @subpackage test
 */
class ILess_Test_Issues_031Test extends ILess_Test_TestCase
{
    protected $oldLocale, $locale;

    public function setUp()
    {
        ILess_Math::restore();
        // reset
        ILess_UnitConversion::restore();
        $this->oldLocale = setlocale(LC_ALL, 0);
        // codes for windows: http://msdn.microsoft.com/en-us/library/39cwe7zf%28v=vs.90%29.aspx
        $this->locale = setlocale(LC_ALL, 'sve', 'sv_SE.utf8');
    }

    public function tearDown()
    {
        setlocale(LC_ALL, $this->oldLocale);
    }

    public function testIssue()
    {
        if ($this->locale === false) {
            $this->markTestSkipped('The test locale could not be set.');
        }

        ILess_Math::setup();
        ILess_UnitConversion::setup();

        $this->assertEquals(array(
                'rad' => '0.1591549430918953',
                'deg' => '0.0027777777777777',
                'grad' => '0.0025000000000000',
                'turn' => '1'
        ), ILess_UnitConversion::$angle, 'The math setup works');
    }

}
