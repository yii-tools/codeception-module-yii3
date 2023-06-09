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
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class Yii3 extends \Codeception\Module
{
    private Aliases $aliaseds;
    private string $argumentRoute = '_language';
    private string $locale = 'en';

    protected array $config = [
        'configPath' => 'config',
        'environment' => '',
        'eventsGroups' => ['events', 'events-console', 'events-web'],
        'namespaceMigration' => [],
        'publicPath' => '',
        'paramsGroups' => ['application-params', 'params', 'params-console', 'params-web'],
        'rootPath' => '',
        'runtimePath' => '',
        'vendorPath' => 'vendor',
    ];
    private ContainerInterface $container;
    private ConfigInterface $configPlugin;
    private PhpBrowser|null $phpBrowser = null;
    private TranslatorInterface $translator;
    private UrlGeneratorInterface $urlGenerator;

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
    }

    public function _initialize(): void
    {
        $this->container = $this->createContainer();

        /** @psalm-var Aliases */
        $this->aliaseds = $this->get(Aliases::class);
        /** @psalm-var TranslatorInterface */
        $this->translator = $this->get(TranslatorInterface::class);
        /** @psalm-var UrlGeneratorInterface */
        $this->urlGenerator = $this->get(UrlGeneratorInterface::class);

        $this->setAliases();
        $this->setUrlDefaultArg();
    }

    public function _inject(PhpBrowser $phpBrowser): void
    {
        $this->phpBrowser = $phpBrowser;
    }

    /**
     * @return string Translates a path alias into an actual path.
     */
    public function alias(string $alias): string
    {
        return $this->aliaseds->get($alias);
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
        $this->getPhpBrowser()->amOnPage($this->urlGenerator->generate($url, $params));
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
        $this->getPhpBrowser()->see($this->translate($id, $category), $selector);
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
        $this->getPhpBrowser()->seeInTitle($this->translate($id, $category));
    }

    /**
     * Set the argument name for the route.
     *
     * @param string $argumentRoute The argument name for the route.
     */
    public function setArgumentRoute(string $argumentRoute): void
    {
        $this->argumentRoute = $argumentRoute;
    }

    /**
     * Set the locale for the application.
     *
     * @param string $locale The locale for the application.
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
        $this->translator->setLocale($locale);

        $this->setUrlDefaultArg();
    }

    /**
     * Set the translated default category.
     *
     * @param string $category The translated default category.
     */
    public function setTranslatedDefaultCategory(string $category): void
    {
        $this->translator = $this->translator->withDefaultCategory($category);
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
    private function createContainer(): ContainerInterface
    {
        /** @var string $configPath */
        $configPath = $this->getConfig('configPath');
        /** @psalm-var string[] $eventsGroup */
        $eventsGroup = $this->getConfig('eventsGroups');
        /** @var string $environment */
        $environment = $this->getConfig('environment');
        /** @psalm-var string[] $paramsGroup */
        $paramsGroup = $this->getConfig('paramsGroups');
        /** @var string $rootPath */
        $rootPath = $this->getConfig('rootPath');
        /** @var string $vendorPath */
        $vendorPath = $this->getConfig('vendorPath');

        $this->configPlugin = new Config(
            new ConfigPaths($rootPath, $configPath, $vendorPath),
            $environment,
            [RecursiveMerge::groups(...$paramsGroup), RecursiveMerge::groups(...$eventsGroup)]
        );

        $definitions = array_merge($this->configPlugin->get('di-console'), $this->configPlugin->get('di-web'));
        $containerConfig = ContainerConfig::create()->withDefinitions($definitions);

        return new Container($containerConfig);
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

    private function getPhpBrowser(): PhpBrowser
    {
        if ($this->phpBrowser !== null) {
            return $this->phpBrowser;
        }

        /** @psalm-var PhpBrowser */
        return $this->getModule('PhpBrowser');
    }

    private function setAliases(): void
    {
        /** @var string $publicPath */
        $publicPath = $this->getConfig('publicPath');
        /** @var string $rootPath */
        $rootPath = $this->getConfig('rootPath');
        /** @var string $runtimePath */
        $runtimePath = $this->getConfig('runtimePath');

        if ($publicPath !== '') {
            $this->aliaseds->set('@public', $publicPath);
        }

        if ($rootPath !== '') {
            $this->aliaseds->set('@root', $rootPath);
        }

        if ($runtimePath !== '') {
            $this->aliaseds->set('@runtime', $runtimePath);
        }
    }

    private function setUrlDefaultArg(): void
    {
        $this->urlGenerator->setDefaultArgument($this->argumentRoute, $this->locale);
    }
}
