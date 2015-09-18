<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Cache\NoCache;
use ILess\Context;
use ILess\Importer;
use ILess\Importer\FileSystemImporter;

/**
 * ILess\Importer tests
 *
 * @package ILess
 * @subpackage test
 * @covers Importer
 */
class Test_ImporterTest extends Test_TestCase
{
    /**
     * @covers registerImporter
     */
    public function testRegisterImporter()
    {
        $env = new Context();
        $i = new Importer($env, [], new NoCache());

        $r = $i->registerImporter(new FileSystemImporter(), 'file_system');
        $i->registerImporter(new FileSystemImporter(), 'disc');

        // fluent interface
        $this->assertInstanceOf('ILess\Importer', $r);
        $this->assertInstanceOf('ILess\Importer\FileSystemImporter', $i->getImporter('file_system'));

        $this->assertInstanceOf('ILess\Importer\FileSystemImporter', $i->getImporter('disc'));
    }

    /**
     * @covers getImporters
     */
    public function testGetImporters()
    {
        $env = new Context();
        $importer = new FileSystemImporter();
        $i = new Importer($env, ['disc' => $importer], new NoCache());

        $this->assertEquals([
            'disc' => $importer
        ], $i->getImporters());
    }

}
