<?php

declare(strict_types=1);

namespace Yii\Codeception\Module;

use Codeception\Lib\ModuleContainer;
use Codeception\Module;
use Codeception\Module\PhpBrowser;
use Codeception\TestInterface;
use ErrorException;
use Psr\Container\ContainerInterface;
use Stringable;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Config\Config;
use Yiisoft\Config\ConfigInterface;
use Yiisoft\Config\ConfigPaths;
use Yiisoft\Config\Modifier\RecursiveMerge;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;
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
        'runtimePath' => '',
        'vendor' => 'vendor',
    ];
    private ContainerInterface $container;
    private ConfigInterface $configPlugin;
    private TranslatorInterface $translator;

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

        if ($argumentRoute !== '') {
            $urlGenerator->setDefaultArgument($argumentRoute, $locale);
            $this->translator->setLocale($locale);
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
     * Return the config instance for Yii3 application.
     */
    public function getConfigPlugin(): ConfigInterface
    {
        return $this->configPlugin;
    }

    /**
     * Return the container instance for Yii3 application.
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Translates a message into the specified language.
     *
     * @param string $id The message ID.
     * @param string|null $category The message category.
     */
    public function translate(string|Stringable $id, string $category = null): string
    {
        return $this->translator->translate($id, category: $category);
    }

    /**
     * Runs migration down.
     *
     * @param array $params The command parameters.
     */
    public function migrationDown(array $params = []): bool
    {
        $command = $this->createCommand('migrate:down');

        return $command->execute($params) === 0;
    }

    /**
     * Runs migration up.
     *
     * @param array $params The command parameters.
     */
    public function migrationUp(array $params = []): bool
    {
        $command = $this->createCommand('migrate:up');

        return $command->execute($params) === 0;
    }

    /**
     * Creates a CommandTester instance for the specified command.
     *
     * @param string $command The command name.
     */
    private function createCommand(string $command): CommandTester
    {
        /** @var array $namespaceMigration */
        $namespaceMigration = $this->getConfig('namespaceMigration') ?? [];

        /** @var MigrationService $migrationService */
        $migrationService = $this->get(MigrationService::class);
        $migrationService->updateNamespaces($namespaceMigration);

        /** @var Application $application */
        $application = $this->get(Application::class);

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

        $this->configPlugin = new Config(
            new ConfigPaths($configPath, 'config', $vendor),
            $environment,
            [RecursiveMerge::groups('params'), RecursiveMerge::groups('events')]
        );

        $definitions = array_merge($this->configPlugin->get('di-console'), $this->configPlugin->get('di-web'));
        $containerConfig = ContainerConfig::create()->withDefinitions($definitions);

        $this->container = new Container($containerConfig);
        $this->translator = $this->container->get(TranslatorInterface::class);

        $this->setAliases();
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

    private function setAliases(): void
    {
        if ($this->getConfig('runtimePath') !== '') {
            /** @var Aliases $aliases */
            $aliases = $this->container->get(Aliases::class);
            $aliases->set('@runtime', (string) $this->getConfig('runtimePath'));
        }
    }
}
