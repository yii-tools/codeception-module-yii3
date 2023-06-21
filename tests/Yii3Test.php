<?php

declare(strict_types=1);

namespace Yii\Codeception\Module\Tests;

use Codeception\Exception\ModuleException;
use Codeception\Lib\Di;
use Codeception\Lib\ModuleContainer;
use Codeception\PHPUnit\TestCase;
use Psr\Container\ContainerInterface;
use Yii\Codeception\Module\Yii3;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Config\ConfigInterface;
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
            [
                'configPath' => __DIR__,
                'environment' => 'test-codeception',
                'namespaceMigration' => ['Yii\\Codeception\\Module\\Tests\\Support'],
                'runtimePath' => __DIR__ . '/runtime',
                'vendor' => '../vendor',
            ],
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

    public function testGet(): void
    {
        $this->assertInstanceOf(UrlGeneratorInterface::class, $this->module->get(UrlGeneratorInterface::class));
    }

    public function testGetConfigPlugin(): void
    {
        $this->assertInstanceOf(ConfigInterface::class, $this->module->getConfigPlugin());
    }

    public function testGetContainer(): void
    {
        $this->assertInstanceOf(ContainerInterface::class, $this->module->getContainer());
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

    public function testRuntimePath(): void
    {
        /** @var Aliases $aliases */
        $aliases = $this->module->get(Aliases::class);

        $this->assertSame(__DIR__ . '/runtime', $aliases->get('@runtime'));
    }

    public function testTranslate(): void
    {
        $this->module->setArgumentRoute('language');
        $this->module->setLocale('es');

        $this->assertSame('', $this->module->amOnRoute('home'));
    }
}
