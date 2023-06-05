<?php

declare(strict_types=1);

namespace Yii\Codeception\Module;

use Codeception\Lib\ModuleContainer;
use Codeception\Module;
use Codeception\Module\PhpBrowser;
use Codeception\TestInterface;
use ErrorException;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Config\Config;
use Yiisoft\Config\ConfigPaths;
use Yiisoft\Config\Modifier\RecursiveMerge;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\Console\Application;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

use function array_merge;

/**
 * Yii3 is a Codeception module for testing Yii3 applications.
 */
final class Yii3 extends Module
{
    protected array $config = [
        'argumentRoute' => '_language',
        'configPath' => null,
        'environment' => null,
        'namespaceMigration' => [],
        'locale' => 'en',
        'vendor' => 'vendor',
    ];
    private ContainerInterface $container;
    private array $params = [];

    /**
     * Constructor.
     *
     * @param ModuleContainer $moduleContainer
     * @param array|null $config
     *
     * @throws ErrorException
     * @throws \Yiisoft\Definitions\Exception\InvalidConfigException
     */
    public function __construct(ModuleContainer $moduleContainer, array|null $config = null)
    {
        parent::__construct($moduleContainer, $config);

        $this->createContainer();
    }

    public function _before(TestInterface $test): void
    {
        /** @var UrlGeneratorInterface $urlGenerator */
        $urlGenerator = $this->container->get(UrlGeneratorInterface::class);
        /** @var string $argumentRoute */
        $argumentRoute = $this->getConfig('argumentRoute') ?? '';
        /** @var string $locale */
        $locale = $this->getConfig('locale') ?? '';

        if ($argumentRoute !== '' && $locale !== '') {
            $urlGenerator->setDefaultArgument($argumentRoute, $locale);
        }
    }

    /**
     * Navigates to the specified route.
     *
     * @param string $url
     * @param array $params
     *
     * @psalm-param array<string, scalar|\Stringable|null> $params
     */
    public function amOnRoute(string $url, array $params = []): void
    {
        /** @var UrlGeneratorInterface $urlGenerator */
        $urlGenerator = $this->container->get(UrlGeneratorInterface::class);
        /** @var PhpBrowser $phpBrowser */
        $phpBrowser = $this->phpBrowser();

        $phpBrowser->amOnPage($urlGenerator->generate($url, $params));
    }

    /**
     * Gets an instance of the specified class from the container.
     *
     * @param string $class The class name or alias name.
     */
    public function get(string $class): object
    {
        /** @psalm-var object */
        return $this->container->get($class);
    }

    /**
     * Return the container instance for Yii3 application.
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Return the params array for Yii3 application.
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Runs migration down.
     */
    public function migrationDown(): bool
    {
        $command = $this->createCommand('migrate:down');
        $command->setInputs(['yes']);
        return (bool) $command->execute(['-a' => '-a']);
    }

    /**
     * Runs migration up.
     */
    public function migrationUp(): bool
    {
        $command = $this->createCommand('migrate:up');
        $command->setInputs(['yes']);
        return (bool) $command->execute([]);
    }

    /**
     * Creates a CommandTester instance for the specified command.
     *
     * @param string $command The command name.
     */
    private function createCommand(string $command): CommandTester
    {
        /** @var array $params */
        $params = $this->params['yiisoft/yii-console'] ?? [];

        /** @var array $namespaceMigration */
        $namespaceMigration = $this->getConfig('namespaceMigration') ?? [];

        /** @var MigrationService $migrationService */
        $migrationService = $this->get(MigrationService::class);
        $migrationService->updateNamespaces($namespaceMigration);

        /** @var Application $application */
        $application = $this->get(Application::class);

        if (array_key_exists('commands', $params)) {
            /** @var array $params */
            $params = $params['commands'];
        }

        //$loader = new ContainerCommandLoader($this->container, $params);
        //$application->setCommandLoader($loader);

        return new CommandTester($application->find($command));
    }

    /**
     * Creates the container.
     *
     * @throws ErrorException
     * @throws \Yiisoft\Definitions\Exception\InvalidConfigException
     */
    private function createContainer(): void
    {
        /** @var string $configPath */
        $configPath = $this->getConfig('configPath') ?? '';
        /** @var string|null $environment */
        $environment = $this->getConfig('environment');
        /** @var string $vendor */
        $vendor = $this->getConfig('vendor');

        $config = new Config(
            new ConfigPaths($configPath, 'config', $vendor),
            $environment,
            [RecursiveMerge::groups('params'), RecursiveMerge::groups('events')]
        );

        $definitions = array_merge($config->get('di-console'), $config->get('di-web'));
        $containerConfig = ContainerConfig::create()->withDefinitions($definitions);

        $this->container = new Container($containerConfig);
        $this->params = $config->get('params');
    }

    /**
     * Gets the value of the specified configuration key.
     *
     * @param string $key The configuration key.
     */
    private function getConfig(string $key): mixed
    {
        return $this->config[$key] ?? null;
    }

    /**
     * Gets the PhpBrowser module instance.
     */
    private function phpBrowser(): Module
    {
        return $this->getModule('PhpBrowser');
    }
}
