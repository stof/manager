<?php

/*
 * This file is part of the puli/manager package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Manager\Tests\Api\Php;

use PHPUnit_Framework_TestCase;
use Puli\Manager\Api\Php\Argument;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ArgumentTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Argument
     */
    private $argument;

    protected function setUp()
    {
        $this->argument = new Argument('argument');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateFailsIfNameNull()
    {
        new Argument(null);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateFailsIfNameEmpty()
    {
        new Argument('');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateFailsIfNameNoString()
    {
        new Argument(1234);
    }

    public function testGetName()
    {
        $this->assertSame('argument', $this->argument->getName());
    }

    public function testSetTypeHint()
    {
        $this->argument->setTypeHint('stdClass');

        $this->assertSame('stdClass', $this->argument->getTypeHint());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetTypeHintFailsIfNull()
    {
        $this->argument->setTypeHint(null);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetTypeHintFailsIfEmpty()
    {
        $this->argument->setTypeHint('');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetTypeHintFailsIfNoString()
    {
        $this->argument->setTypeHint(1234);
    }

    public function testRemoveTypeHint()
    {
        $this->argument->setTypeHint('stdClass');
        $this->argument->removeTypeHint();

        $this->assertNull($this->argument->getTypeHint());
    }

    public function testHasTypeHint()
    {
        $this->assertFalse($this->argument->hasTypeHint());

        $this->argument->setTypeHint('stdClass');

        $this->assertTrue($this->argument->hasTypeHint());

        $this->argument->removeTypeHint();

        $this->assertFalse($this->argument->hasTypeHint());
    }

    public function testSetType()
    {
        $this->argument->setType('string');

        $this->assertSame('string', $this->argument->getType());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetTypeFailsIfNull()
    {
        $this->argument->setType(null);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetTypeFailsIfEmpty()
    {
        $this->argument->setType('');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetTypeFailsIfNoString()
    {
        $this->argument->setType(1234);
    }

    public function testSetDescription()
    {
        $this->argument->setDescription('New description');

        $this->assertSame('New description', $this->argument->getDescription());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetDescriptionFailsIfNull()
    {
        $this->argument->setDescription(null);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetDescriptionFailsIfEmpty()
    {
        $this->argument->setDescription('');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetDescriptionFailsIfNoString()
    {
        $this->argument->setDescription(1234);
    }

    public function testSetDefaultValue()
    {
        $this->argument->setDefaultValue('42');

        $this->assertSame('42', $this->argument->getDefaultValue());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetDefaultValueFailsIfNull()
    {
        $this->argument->setDefaultValue(null);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetDefaultValueFailsIfEmpty()
    {
        $this->argument->setDefaultValue('');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetDefaultValueFailsIfNoString()
    {
        $this->argument->setDefaultValue(1234);
    }

    public function testRemoveDefaultValue()
    {
        $this->argument->setDefaultValue('42');
        $this->argument->removeDefaultValue();

        $this->assertNull($this->argument->getDefaultValue());
    }

    public function testHasDefaultValue()
    {
        $this->assertFalse($this->argument->hasDefaultValue());

        $this->argument->setDefaultValue('42');

        $this->assertTrue($this->argument->hasDefaultValue());

        $this->argument->removeDefaultValue();

        $this->assertFalse($this->argument->hasDefaultValue());
    }
}
