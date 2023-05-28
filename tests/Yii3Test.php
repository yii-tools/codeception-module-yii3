<?php

declare(strict_types=1);

namespace Yii\Codeception\Module\Tests;

use Codeception\Exception\ModuleException;
use Codeception\Lib\Di;
use Codeception\Lib\ModuleContainer;
use Codeception\PHPUnit\TestCase;
use Codeception\TestInterface;
use Psr\Container\ContainerInterface;
use Yii\Codeception\Module\Yii3;
use Yii\Support\Assert;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * Test for Yii3 module.
 */
final class Yii3Test extends TestCase
{
    private Yii3 $module;

    public function setup(): void
    {
        parent::setUp();

        $this->module = new Yii3(
            new ModuleContainer(new Di(), []),
            ['configPath' => __DIR__, 'environment' => 'test-codeception', 'vendor' => '../vendor'],
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();

        unset($this->module);
    }

    public function testAmOnRoute(): void
    {
        $this->expectException(ModuleException::class);
        $this->expectExceptionMessage("Codeception\Module: Module PhpBrowser couldn't be connected");

        $this->module->amOnRoute('site/index');
    }

    public function testBefore(): void
    {
        /** @var UrlGeneratorInterface $urlGenerator */
        $urlGenerator = $this->module->get(UrlGeneratorInterface::class);
        /** @var TestInterface $testInterface */
        $testInterface = $this->createMock(TestInterface::class);

        $this->module->_before($testInterface);

        $this->assertSame(['_language' => 'en'], Assert::inaccessibleProperty($urlGenerator, 'defaultArguments'));
    }

    public function testGet(): void
    {
        $this->assertInstanceOf(UrlGeneratorInterface::class, $this->module->get(UrlGeneratorInterface::class));
    }

    public function testGetContainer(): void
    {
        $this->assertInstanceOf(ContainerInterface::class, $this->module->getContainer());
    }

    public function testGetParams(): void
    {
        $this->assertIsArray($this->module->getParams());
    }

    /**
     * @depends testMigrationUp
     */
    public function testMigrationDown(): void
    {
        $this->assertTrue($this->module->migrationDown());
    }

    public function testMigrationUp(): void
    {
        $this->assertTrue($this->module->migrationUp());
    }
}
