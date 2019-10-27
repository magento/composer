<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Composer\Tests;

use Magento\Composer\ConsoleArrayInputFactory;
use Symfony\Component\Console\Input\ArrayInput;
use PHPUnit\Framework\TestCase;

class ConsoleArrayInputFactoryTest extends TestCase
{

    /**
     * @var ConsoleArrayInputFactory
     */
    protected $factory;

    protected function setUp(): void
    {
        $this->factory = new ConsoleArrayInputFactory();
    }

    public function testCreate()
    {
        $this->assertInstanceOf(ArrayInput::class, $this->factory->create([]));
    }
}
