<?php

namespace ILess\Test;

class UnitConversionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetGroups()
    {
        $groups = \ILess\Util\UnitConversion::getGroups();
        $this->assertEquals(
            [
                'length',
                'duration',
                'angle',
            ],
            $groups
        );
    }

    public function testGetGroup()
    {
        $group = \ILess\Util\UnitConversion::getGroup('length');
        $this->assertInternalType('array', $group);

        $group = \ILess\Util\UnitConversion::getGroup('duration');
        $this->assertInternalType('array', $group);

        $group = \ILess\Util\UnitConversion::getGroup('angle');
        $this->assertInternalType('array', $group);

        $group = \ILess\Util\UnitConversion::getGroup('invalid');
        $this->assertInternalType('null', $group);
    }

}
