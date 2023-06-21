<?php

declare(strict_types=1);

namespace Yii\Codeception\Module\Tests;

use Codeception\Configuration;
use Codeception\Lib\Di;
use Codeception\Lib\ModuleContainer;
use Codeception\PHPUnit\TestCase;
use HttpSoft\Message\RequestFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Yii\Codeception\Module\Yii3;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Config\ConfigInterface;
use Yiisoft\Router\RouteNotFoundException;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

/**
 * Test for Yii3 module.
 */
final class Yii3Test extends TestCase
{
    private Yii3 $module;

    public function setup(): void
    {
        parent::setUp();

        // configure output path
        Configuration::append(['paths' => ['output' => __DIR__ . '/_output']]);

        // configure yii3 module
        $this->module = new Yii3(
            new ModuleContainer(new Di(), []),
            [
                'configPath' => 'tests/data/config',
                'rootPath' => dirname(__DIR__),
                'namespaceMigration' => ['Yii\\Codeception\\Module\\Tests\\Support'],
                'runtimePath' => __DIR__ . '/runtime',
                'vendor' => '../../../vendor',
            ],
        );
        $this->module->_initialize();
        $this->module->setArgumentRoute('_language');
    }

    public function tearDown(): void
    {
        parent::tearDown();

        unset($this->module);
    }

    public function testAmOnRoute(): void
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('Cannot generate URI for route "site/index"; route not found.');

        $this->module->amOnRoute('site/index');
    }

    public function testGet(): void
    {
        $this->assertInstanceOf(MigrationService::class, $this->module->get(MigrationService::class));
        $this->assertInstanceOf(RequestFactory::class, $this->module->get(RequestFactoryInterface::class));

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

    public function testSeeTranslated(): void
    {
        $this->module->amOnRoute('home');

        $this->module->seeTranslated('site.description');
    }

    public function testSeeTranslatedWithLocale(): void
    {
        $this->module->setLocale('es');

        $this->module->amOnRoute('home');

        $this->module->seeTranslated('site.description');
    }

    public function testSeeTranslatedInTitle(): void
    {
        $this->module->amOnRoute('home');

        $this->module->seeTranslatedInTitle('site.menu.home');
    }

    public function testSeeTranslatedInTitleWithLocale(): void
    {
        $this->module->setLocale('es');

        $this->module->amOnRoute('home');

        $this->module->seeTranslatedInTitle('site.menu.home');
    }

    public function testRuntimePath(): void
    {
        /** @var Aliases $aliases */
        $aliases = $this->module->get(Aliases::class);

        $this->assertSame(__DIR__ . '/runtime', $aliases->get('@runtime'));
    }
}
