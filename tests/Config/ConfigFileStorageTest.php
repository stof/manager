<?php

/*
 * This file is part of the puli/manager package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Manager\Tests\Config;

use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Puli\Manager\Api\Config\Config;
use Puli\Manager\Api\Config\ConfigFile;
use Puli\Manager\Api\Config\ConfigFileReader;
use Puli\Manager\Api\Config\ConfigFileWriter;
use Puli\Manager\Api\FileNotFoundException;
use Puli\Manager\Config\ConfigFileStorage;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ConfigFileStorageTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigFileStorage
     */
    private $storage;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|ConfigFileReader
     */
    private $reader;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|ConfigFileWriter
     */
    private $writer;

    protected function setUp()
    {
        $this->reader = $this->getMock('Puli\Manager\Api\Config\ConfigFileReader');
        $this->writer = $this->getMock('Puli\Manager\Api\Config\ConfigFileWriter');

        $this->storage = new ConfigFileStorage($this->reader, $this->writer);
    }

    public function testLoadConfigFile()
    {
        $configFile = new ConfigFile();

        $this->reader->expects($this->once())
            ->method('readConfigFile')
            ->with('/path')
            ->will($this->returnValue($configFile));

        $this->assertSame($configFile, $this->storage->loadConfigFile('/path'));
    }

    public function testLoadConfigFileWithBaseConfig()
    {
        $baseConfig = new Config();
        $configFile = new ConfigFile();

        $this->reader->expects($this->once())
            ->method('readConfigFile')
            ->with('/path', $baseConfig)
            ->will($this->returnValue($configFile));

        $this->assertSame($configFile, $this->storage->loadConfigFile('/path', $baseConfig));
    }

    public function testLoadConfigFileCreatesNewIfNotFound()
    {
        $this->reader->expects($this->once())
            ->method('readConfigFile')
            ->with('/path')
            ->will($this->throwException(new FileNotFoundException()));

        $this->assertEquals(new ConfigFile('/path'), $this->storage->loadConfigFile('/path'));
    }

    public function testLoadConfigFileWithBaseConfigCreatesNewIfNotFound()
    {
        $baseConfig = new Config();

        $this->reader->expects($this->once())
            ->method('readConfigFile')
            ->with('/path', $baseConfig)
            ->will($this->throwException(new FileNotFoundException()));

        $this->assertEquals(new ConfigFile('/path', $baseConfig), $this->storage->loadConfigFile('/path', $baseConfig));
    }

    public function testSaveConfigFile()
    {
        $configFile = new ConfigFile('/path');

        $this->writer->expects($this->once())
            ->method('writeConfigFile')
            ->with($configFile, '/path');

        $this->storage->saveConfigFile($configFile);
    }

    public function testSaveConfigFileGeneratesFactoryIfManagerAvailable()
    {
        $configFile = new ConfigFile('/path');

        $factoryManager = $this->getMock('Puli\Manager\Api\Factory\FactoryManager');
        $storage = new ConfigFileStorage($this->reader, $this->writer, $factoryManager);

        $factoryManager->expects($this->once())
            ->method('autoGenerateFactoryClass');

        $storage->saveConfigFile($configFile);
    }
}
