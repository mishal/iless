<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Importer tests
 *
 * @package ILess
 * @subpackage test
 * @covers ILess_Importer
 */
class ILess_Test_ImporterTest extends ILess_Test_TestCase
{
    /**
     * @covers registerImporter
     */
    public function testRegisterImporter()
    {
        $env = new ILess_Environment();
        $i = new ILess_Importer($env, array(), new ILess_Cache_None());

        $r = $i->registerImporter(new ILess_Importer_FileSystem(), 'file_system');
        $i->registerImporter(new ILess_Importer_FileSystem(), 'disc');

        // fluent interface
        $this->assertInstanceOf('ILess_Importer', $r);
        $this->assertInstanceOf('ILess_Importer_FileSystem', $i->getImporter('file_system'));

        $this->assertInstanceOf('ILess_Importer_FileSystem', $i->getImporter('disc'));
    }

    /**
     * @covers getImporters
     */
    public function testGetImporters()
    {
        $env = new ILess_Environment();
        $importer = new ILess_Importer_FileSystem();
        $i = new ILess_Importer($env, array('disc' => $importer), new ILess_Cache_None());

        $this->assertEquals(array(
            'disc' => $importer
        ), $i->getImporters());
    }

}
