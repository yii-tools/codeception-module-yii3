<?php

declare(strict_types=1);

namespace Yii\Codeception\Module;

use Codeception\Lib\ModuleContainer;
use Codeception\Module\PhpBrowser;
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
final class Yii3 extends PhpBrowser
{
    private string $argumentRoute = '_language';
    private string $locale = 'en';

    protected array $config = [
        // yii3 module config
        'configPath' => 'config',
        'environment' => '',
        'namespaceMigration' => [],
        'rootPath' => '',
        'runtimePath' => '',
        'vendorPath' => 'vendor',

        // curl module config
        'curl' => [],
        'expect' => false,
        'handler' => 'curl',
        'headers' => [],
        'middleware' => null,
        'refresh_max_interval' => 10,
        'timeout' => 30,
        'url' => 'http://localhost:8080',
        'verify' => false,

        // required defaults (not recommended to change)
        'allow_redirects' => false,
        'http_errors' => false,
        'cookies' => true,
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
        $urlGenerator->setDefaultArgument($this->argumentRoute, $this->locale);
        $this->translator->setLocale($this->locale);

        $this->amOnPage($urlGenerator->generate($url, $params));
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
     * Verifies that a translated text is present on the current page.
     *
     * @param string|Stringable $id The ID of the message to translate.
     * @param string|null $category The message category (optional).
     * @param array|string|null $selector Selector to search for the text on the page (optional).
     *
     * @throws \Codeception\Exception\ElementNotFound If the text is not present on the page.
     */
    public function seeTranslated(string|Stringable $id, string $category = null, array|string $selector = null): void
    {
        $this->see($this->translate($id, $category), $selector);
    }

    /**
     * Verifies that a translated text is present in the page title.
     *
     * @param string|Stringable $id The ID of the message to translate.
     * @param string|null $category The message category (optional).
     *
     * @throws \Codeception\Exception\ElementNotFound If the text is not present in the page title.
     */
    public function seeTranslatedInTitle(string|Stringable $id, string $category = null): void
    {
        $this->seeInTitle($this->translate($id, $category));
    }

    public function setArgumentRoute(string $argumentRoute): void
    {
        $this->argumentRoute = $argumentRoute;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function translate(
        string|Stringable $id,
        string $category = null,
        array $params = [],
        string $language = null
    ): string {
        return $this->translator->translate($id, $params, $category, $language);
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
        $configPath = $this->getConfig('configPath');
        /** @var string $rootPath */
        $rootPath = $this->getConfig('rootPath');
        /** @var string $environment */
        $environment = $this->getConfig('environment');
        /** @var string $vendorPath */
        $vendorPath = $this->getConfig('vendorPath');

        $this->configPlugin = new Config(
            new ConfigPaths($rootPath, $configPath, $vendorPath),
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

    private function setAliases(): void
    {
        if ($this->getConfig('runtimePath') !== '') {
            /** @var Aliases $aliases */
            $aliases = $this->container->get(Aliases::class);
            $aliases->set('@runtime', (string) $this->getConfig('runtimePath'));
        }
    }
}
