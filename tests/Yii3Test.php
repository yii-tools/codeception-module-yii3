<?php

declare(strict_types=1);

namespace Yii\Codeception\Tests\Unit;

use Codeception\Configuration;
use Codeception\Lib\Di;
use Codeception\Lib\ModuleContainer;
use Codeception\Module\PhpBrowser;
use Codeception\Test\Unit;
use HttpSoft\Message\RequestFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Yii\Codeception\Module\Yii3;
use Yiisoft\Config\ConfigInterface;
use Yiisoft\Router\RouteNotFoundException;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

/**
 * Test unit for module `Yii3`.
 */
final class Yii3Test extends Unit
{
    private Yii3 $module;

    public function setup(): void
    {
        // configure output path
        Configuration::append(['paths' => ['output' => __DIR__ . '/_output']]);

        $moduleContainer = new ModuleContainer(new Di(), []);

        $phpBrowser = new PhpBrowser($moduleContainer);
        $url = 'http://localhost:8080';

        $phpBrowser->_setConfig(['url' => $url]);
        $phpBrowser->_initialize();

        // configure yii3 module
        $this->module = new Yii3(
            $moduleContainer,
            [
                'configPath' => 'tests/_data/config',
                'namespaceMigration' => ['Yii\\Codeception\\Module\Tests\\Support'],
                'publicPath' => '@root/tests/_data/public',
                'rootPath' => dirname(__DIR__, 1),
                'runtimePath' => '@root/tests/runtime',
                'vendor' => '../../../vendor',
            ],
        );
        $this->module->_inject($phpBrowser);
        $this->module->_initialize();
        $this->module->setArgumentRoute('_language');
    }

    public function tearDown(): void
    {
        parent::tearDown();

        unset($this->module);
    }

    public function testAliases(): void
    {
        $this->assertSame(dirname(__DIR__), $this->module->alias('@root'));
        $this->assertSame(dirname(__DIR__) . '/tests/_data/public', $this->module->alias('@public'));
        $this->assertSame(dirname(__DIR__) . '/tests/runtime', $this->module->alias('@runtime'));
        $this->assertSame(dirname(__DIR__) . '/vendor', $this->module->alias('@vendor'));
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

    public function testTranslate(): void
    {
        $this->module->amOnRoute('home');

        $this->assertSame('Home', $this->module->translate('site.menu.home'));
    }

    public function testSetTranslatedDefaultCategory(): void
    {
        $this->module->setTranslatedDefaultCategory('app');

        $this->module->amOnRoute('home');

        $this->module->seeTranslated('site.menu.home');
    }

    public function testSeeTranslatedInTitleWithLocale(): void
    {
        $this->module->setLocale('es');

        $this->module->amOnRoute('home');

        $this->module->seeTranslatedInTitle('site.menu.home');
    }
}
