<?php

namespace FailAid\Tests\Extension\ServiceContainer;

use FailAid\Extension\ServiceContainer\Extension;
use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adapts Extension::configure() to ConfigurationInterface for config tree testing.
 */
class ExtensionConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('FailAidExtension');
        (new Extension())->configure($treeBuilder->getRootNode());
        return $treeBuilder;
    }
}

class ExtensionTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    protected function getConfiguration(): ConfigurationInterface
    {
        return new ExtensionConfiguration();
    }

    // --- configure() / config tree tests ---

    public function testDefaultConfigIsValid(): void
    {
        $this->assertConfigurationIsValid([[]]);
    }

    public function testDefaultOutputOptions(): void
    {
        $this->assertProcessedConfigurationEquals([[]], [
            'screenshot' => [
                'directory' => null,
                'mode' => 'html',
                'size' => null,
                'autoClean' => false,
                'hostDirectory' => null,
                'hostUrl' => null,
            ],
            'output' => [
                'api' => false,
                'url' => true,
                'status' => true,
                'feature' => true,
                'context' => true,
                'screenshot' => true,
                'driver' => true,
                'debugBarSelectors' => true,
                'rerun' => true,
                'tags' => true,
            ],
            'trackJs' => [
                'errors' => false,
                'warns' => false,
                'logs' => false,
                'trim' => false,
            ],
            'defaultSession' => null,
            'screenshotDirectory' => null,
            'screenshotMode' => 'html',
        ]);
    }

    public function testCustomScreenshotOptions(): void
    {
        $this->assertProcessedConfigurationEquals([
            [
                'screenshot' => [
                    'directory' => '/tmp/screenshots',
                    'mode' => 'png',
                    'autoClean' => true,
                    'size' => '1024x768',
                    'hostUrl' => 'http://ci.example.com/screenshots/',
                ],
            ],
        ], [
            'screenshot' => [
                'directory' => '/tmp/screenshots',
                'mode' => 'png',
                'size' => '1024x768',
                'autoClean' => true,
                'hostDirectory' => null,
                'hostUrl' => 'http://ci.example.com/screenshots/',
            ],
            'output' => [
                'api' => false,
                'url' => true,
                'status' => true,
                'feature' => true,
                'context' => true,
                'screenshot' => true,
                'driver' => true,
                'debugBarSelectors' => true,
                'rerun' => true,
                'tags' => true,
            ],
            'trackJs' => [
                'errors' => false,
                'warns' => false,
                'logs' => false,
                'trim' => false,
            ],
            'defaultSession' => null,
            'screenshotDirectory' => null,
            'screenshotMode' => 'html',
        ]);
    }

    public function testApiModeOption(): void
    {
        $this->assertProcessedConfigurationEquals(
            [['output' => ['api' => true]]],
            [
                'screenshot' => [
                    'directory' => null,
                    'mode' => 'html',
                    'size' => null,
                    'autoClean' => false,
                    'hostDirectory' => null,
                    'hostUrl' => null,
                ],
                'output' => [
                    'api' => true,
                    'url' => true,
                    'status' => true,
                    'feature' => true,
                    'context' => true,
                    'screenshot' => true,
                    'driver' => true,
                    'debugBarSelectors' => true,
                    'rerun' => true,
                    'tags' => true,
                ],
                'trackJs' => [
                    'errors' => false,
                    'warns' => false,
                    'logs' => false,
                    'trim' => false,
                ],
                'defaultSession' => null,
                'screenshotDirectory' => null,
                'screenshotMode' => 'html',
            ]
        );
    }

    public function testDebugBarSelectorsAreAccepted(): void
    {
        $this->assertConfigurationIsValid([[
            'debugBarSelectors' => [
                'message' => '.debugBar .message',
                'queries' => '.debugBar .queries',
            ],
        ]]);
    }

    public function testSiteFiltersAreAccepted(): void
    {
        $this->assertConfigurationIsValid([[
            'siteFilters' => [
                '/js/' => 'http://cdn.example.com/js/',
                '/css/' => 'http://cdn.example.com/css/',
            ],
        ]]);
    }

    public function testTrackJsOptions(): void
    {
        $this->assertProcessedConfigurationEquals(
            [['trackJs' => ['errors' => true, 'warns' => true, 'trim' => 50]]],
            [
                'screenshot' => [
                    'directory' => null,
                    'mode' => 'html',
                    'size' => null,
                    'autoClean' => false,
                    'hostDirectory' => null,
                    'hostUrl' => null,
                ],
                'output' => [
                    'api' => false,
                    'url' => true,
                    'status' => true,
                    'feature' => true,
                    'context' => true,
                    'screenshot' => true,
                    'driver' => true,
                    'debugBarSelectors' => true,
                    'rerun' => true,
                    'tags' => true,
                ],
                'trackJs' => [
                    'errors' => true,
                    'warns' => true,
                    'logs' => false,
                    'trim' => 50,
                ],
                'defaultSession' => null,
                'screenshotDirectory' => null,
                'screenshotMode' => 'html',
            ]
        );
    }

    // --- load() / container tests ---

    private function loadExtension(array $config = []): ContainerBuilder
    {
        $processedConfig = (new Processor())->processConfiguration(
            new ExtensionConfiguration(),
            [$config]
        );

        $container = new ContainerBuilder();
        (new Extension())->load($container, $processedConfig);
        return $container;
    }

    public function testLoadSetsDefaultOutputParameter(): void
    {
        $container = $this->loadExtension();

        $output = $container->getParameter('failaid.config.output');
        $this->assertFalse($output['api']);
        $this->assertTrue($output['url']);
        $this->assertTrue($output['status']);
        $this->assertTrue($output['screenshot']);
        $this->assertTrue($output['debugBarSelectors']);
    }

    public function testLoadSetsScreenshotParameterFromScreenshotNode(): void
    {
        $container = $this->loadExtension([
            'screenshot' => ['directory' => '/tmp/shots', 'mode' => 'png'],
        ]);

        $screenshot = $container->getParameter('failaid.config.screenshot');
        $this->assertSame('/tmp/shots', $screenshot['directory']);
        $this->assertSame('png', $screenshot['mode']);
    }

    public function testLoadSetsEmptyDebugBarSelectorsWhenNotProvided(): void
    {
        $container = $this->loadExtension();

        $this->assertSame([], $container->getParameter('failaid.config.debugBarSelectors'));
    }

    public function testLoadSetsDebugBarSelectorsParameter(): void
    {
        $container = $this->loadExtension([
            'debugBarSelectors' => ['msg' => '.bar .msg', 'queries' => '.bar .queries'],
        ]);

        $selectors = $container->getParameter('failaid.config.debugBarSelectors');
        $this->assertSame('.bar .msg', $selectors['msg']);
        $this->assertSame('.bar .queries', $selectors['queries']);
    }

    public function testLoadSetsSiteFiltersParameter(): void
    {
        $container = $this->loadExtension([
            'siteFilters' => ['/js/' => 'http://cdn.example.com/js/'],
        ]);

        $filters = $container->getParameter('failaid.config.siteFilters');
        $this->assertSame('http://cdn.example.com/js/', $filters['/js/']);
    }

    public function testLoadSetsTrackJsParameter(): void
    {
        $container = $this->loadExtension([
            'trackJs' => ['errors' => true, 'trim' => 50],
        ]);

        $trackJs = $container->getParameter('failaid.config.trackJs');
        $this->assertTrue($trackJs['errors']);
        $this->assertSame(50, $trackJs['trim']);
    }

    public function testLoadSetsDefaultSessionParameter(): void
    {
        $container = $this->loadExtension(['defaultSession' => 'javascript']);

        $this->assertSame(
            'javascript',
            $container->getParameter('failaid.config.defaultSession')
        );
    }

    public function testLoadRegistersContextInitialiser(): void
    {
        $container = $this->loadExtension();

        $this->assertTrue($container->hasDefinition(Extension::CONTEXT_INITIALISER));
    }

    public function testLoadRegistersCliCommands(): void
    {
        $container = $this->loadExtension();

        $this->assertTrue($container->hasDefinition('cli.controller.failaid.scenariodebug'));
        $this->assertTrue($container->hasDefinition('cli.controller.failaid.clearScreenshots'));
        $this->assertTrue($container->hasDefinition('cli.controller.failaid.waitOnFailure'));
        $this->assertTrue($container->hasDefinition('cli.controller.failaid.feedbackOnFailure'));
    }
}
