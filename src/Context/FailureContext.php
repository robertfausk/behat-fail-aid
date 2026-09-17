<?php

declare(strict_types=1);

namespace FailAid\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Behat\Hook\Scope\ScenarioScope;
use Behat\Mink\Driver\DriverInterface;
use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use Behat\MinkExtension\Context\MinkAwareContext;
use Behat\Testwork\ServiceContainer\Configuration\ConfigurationLoader;
use Behat\Testwork\Tester\Result\ExceptionResult;
use Behat\Testwork\Tester\Result\TestResult;
use Exception;
use FailAid\Context\Contracts\DebugBarInterface;
use FailAid\Context\Contracts\FailStateInterface;
use FailAid\Service\JSDebug;
use FailAid\Service\Output;
use FailAid\Service\Screenshot;
use FailAid\Service\StaticCallerService;
use Symfony\Component\Console\Input\ArgvInput;

class FailureContext implements MinkAwareContext, FailStateInterface, DebugBarInterface
{
    /**
     * @var string|null
     */
    public $defaultSession;

    /**
     * @var Mink
     */
    private $mink;

    /**
     * @var array<string, mixed>
     */
    private $minkParameters;

    /**
     * @var array<string, mixed>
     */
    private $debugBarSelectors = [];

    /**
     * @var string|null
     */
    private static $exceptionHash;

    /**
     * @var array<string, mixed>
     */
    private static $states = [];

    /**
     * @var bool
     */
    private static $cleaned = false;

    /**
     * @var ScenarioScope|null
     */
    private $currentScenario;

    /**
     * @var bool
     */
    private static $debugScenario = false;

    /**
     * @var int
     */
    private static $waitOnFailure = 0;

    /**
     * @var bool
     */
    private static $autoClean = false;

    /**
     * @var bool
     */
    private static $feedbackOnFailure = false;

    /**
     * @var self|null
     */
    private static $self;

    /**
     * @var array<string, mixed>
     */
    private $outputOptions = [];

    /**
     * @var StaticCallerService
     */
    public $staticCaller;

    /**
     * @param array<string, mixed> $output
     */
    public function __construct(array $output = [])
    {
        $this->outputOptions = $output;
        self::$self = $this;
    }

    public static function getInstance(): ?self
    {
        return self::$self;
    }

    public function setStaticCaller(StaticCallerService $staticCaller): self
    {
        $this->staticCaller = $staticCaller;

        return $this;
    }

    /**
     * @param array<string, mixed>  $screenshot
     * @param array<string, string> $siteFilters
     * @param array<string, mixed>  $debugBarSelectors
     * @param array<string, mixed>  $trackJs
     * @param array<string, mixed>  $outputOptions
     */
    public function setConfig(
        array $screenshot = [],
        array $siteFilters = [],
        array $debugBarSelectors = [],
        array $trackJs = ['errors' => false, 'logs' => false, 'warns' => false, 'trim' => false],
        ?string $defaultSession = null,
        array $outputOptions = [],
    ): void {
        $this->debugBarSelectors = $debugBarSelectors;
        $this->defaultSession = $defaultSession;
        $this->staticCaller->call(Screenshot::class, 'setOptions', [$screenshot, $siteFilters]);
        $this->staticCaller->call(JSDebug::class, 'setOptions', [$trackJs]);
        $this->staticCaller->call(Output::class, 'setOptions', [$outputOptions]);

        if ($this->outputOptions) {
            foreach ($this->outputOptions as $option => $value) {
                $this->staticCaller->call(Output::class, 'setOption', [$option, $value]);
            }
        }
    }

    /**
     * @Given I take a screenshot
     */
    public function iTakeAScreenshot(): void
    {
        $session = $this->getSession();
        try {
            $this->staticCaller->call(Screenshot::class, 'canTakeScreenshot', [$session]);
            $screenshotPath = $this->staticCaller->call(Screenshot::class, 'takeScreenshot', [
                $session->getPage(),
                $session->getDriver(),
            ]);

            echo '[SCREENSHOT] '.$screenshotPath;
        } catch (\Exception $e) {
            echo 'Unable to take screenshot: '.$e->getMessage();
        }
    }

    /**
     * @Given I gather facts for the current state
     */
    public function iGatherFactsForTheCurrentState(): void
    {
        $session = $this->getSession();
        $driver = $session->getDriver();

        echo $this->gatherFacts(
            $session,
            $driver,
            $this->debugBarSelectors,
            'NA',
            'NA',
            $this->currentScenario
        );
    }

    /**
     * @BeforeSuite
     *
     * Load the config file again as the context params aren't available until the context is initialised.
     */
    public static function autoCleanBeforeTestExecution($arg1): void
    {
        if (self::$cleaned) {
            return;
        }

        $configPath = self::getConfigFilePath();
        $config = (new ConfigurationLoader('BEHAT_PARAMS', $configPath))->loadConfiguration();

        if (!isset($config[0]['extensions']['FailAid\\Extension']['screenshot'])) {
            return;
        }

        $screenshotConfig = $config[0]['extensions']['FailAid\\Extension']['screenshot'];

        if (!$screenshotConfig['autoClean'] && !self::$autoClean) {
            return;
        }

        $directory = isset($screenshotConfig['directory']) ? $screenshotConfig['directory'] : sys_get_temp_dir();
        self::clearDir($directory);

        self::$cleaned = true;
    }

    /**
     * @BeforeScenario
     */
    public function currentScenario($scenarioEvent): self
    {
        $this->currentScenario = $scenarioEvent;

        return $this;
    }

    /**
     * @BeforeScenario
     */
    public function refreshStates(): void
    {
        self::$states = [];
    }

    /**
     * @AfterStep
     */
    public function takeScenarioScreenShot(AfterStepScope $scope): void
    {
        if (self::$debugScenario) {
            try {
                $this->iTakeAScreenshot();
            } catch (\Exception $e) {
                // Ignore...
            }
        }
    }

    /**
     * @AfterStep
     */
    public function gatherStateFactsAfterFailedStep(AfterStepScope $scope): ?string
    {
        if (TestResult::FAILED === $scope->getTestResult()->getResultCode()) {
            try {
                $message = null;
                $testResult = $scope->getTestResult();

                if (!$testResult instanceof ExceptionResult) {
                    self::$exceptionHash = null;

                    return null;
                }

                $exception = $testResult->getException();
                if (null === $exception) {
                    self::$exceptionHash = null;

                    return null;
                }

                // To get away from appending exception details multiple times in one lifecycle
                // of a test suite - we need to make sure the exception thrown is different
                // from the previous one before working with it. This happens because each scenario
                // initialises new context files but the exception remains the same, and each context
                // goes through the afterStep.
                $objectHash = spl_object_hash($exception);
                if (self::$exceptionHash !== $objectHash) {
                    self::$exceptionHash = $objectHash;

                    $message = '';
                    if (!$this->staticCaller->call(Output::class, 'getOption', ['api'])) {
                        if ($this->staticCaller->call(Output::class, 'getOption', ['screenshot'])) {
                            try {
                                $this->getSession()->getPage()->getHtml();
                            } catch (\WebDriver\Exception\NoSuchElement $e) {
                                $message = \PHP_EOL.\PHP_EOL.'The page is blank, is the driver/browser ready to receive the request?';
                            }
                        }

                        $session = $this->getSession();
                        $driver = $session->getDriver();

                        $message .= $this->gatherFacts(
                            $session,
                            $driver,
                            $this->debugBarSelectors,
                            $scope->getFeature()->getFile(),
                            $exception->getFile(),
                            $this->currentScenario
                        );
                    } else {
                        $this->staticCaller->call(Output::class, 'setOption', ['url', false]);
                        $this->staticCaller->call(Output::class, 'setOption', ['status', false]);
                        $this->staticCaller->call(Output::class, 'setOption', ['screenshot', false]);
                        $this->staticCaller->call(Output::class, 'setOption', ['driver', false]);
                        $this->staticCaller->call(Output::class, 'setOption', ['rerun', false]);

                        $message = $this->staticCaller->call(Output::class, 'getExceptionDetails', [
                            null,
                            null,
                            $scope->getFeature()->getFile(),
                            $exception->getFile(),
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            $this->currentScenario,
                        ]);
                    }

                    $message = $this->addStateDetails($message, $this->getStateDetails(self::$states));

                    $this->setAdditionalExceptionDetailsInException(
                        $exception,
                        $message
                    );
                }

                if (self::$waitOnFailure) {
                    echo \sprintf('Waiting on failure for %d seconds', self::$waitOnFailure).\PHP_EOL;
                }

                if (self::$feedbackOnFailure) {
                    echo \PHP_EOL.'-- FAIL --'.\PHP_EOL.$exception->getMessage();
                    ob_flush();
                }

                return $message;
            } catch (DriverException $e) {
                // The driver is not available, dont fail - allow behat to print out the actual error message.
                echo 'Error message: '.$e->getMessage();
            }
        }

        self::$exceptionHash = null;

        return null;
    }

    /**
     * @AfterScenario
     */
    public function waitOnFailure(): void
    {
        if (self::$exceptionHash) {
            if (self::$waitOnFailure) {
                sleep(self::$waitOnFailure);
            }
        }
    }

    public static function setDebugScenario(bool $bool): void
    {
        self::$debugScenario = $bool;
    }

    public static function setWaitOnFailure(int $time): void
    {
        self::$waitOnFailure = (int) $time;
    }

    public static function setFeedbackOnFailure(bool $bool): void
    {
        self::$feedbackOnFailure = $bool;
    }

    public static function clearDir(string $directory): void
    {
        $extensions = ['png', 'html'];
        foreach (new \DirectoryIterator($directory) as $file) {
            if ($file->isFile() && \in_array($file->getExtension(), $extensions)) {
                unlink($directory.\DIRECTORY_SEPARATOR.$file->getFilename());
            }
        }
    }

    public static function setAutoClean(bool $bool): void
    {
        self::$autoClean = $bool;
    }

    private static function getConfigFilePath(): string
    {
        $input = new ArgvInput();
        $path = $input->getParameterOption(['-c', '--config'], 'behat.yml');
        $basePath = '';

        if ('/' !== substr($path, 0, 1)) {
            $basePath = self::getBasePathForFile($path, (string) getcwd()).\DIRECTORY_SEPARATOR;
        }

        $configFile = $basePath.$path;

        if (!file_exists($configFile)) {
            throw new \Exception("Autoclean: Config file '$path' not found at base path: '$basePath',
                please pass in the path to the config file through the -c flag and check permissions.");
        }

        return $configFile;
    }

    private static function getBasePathForFile(string $file, string $path): string
    {
        if (!file_exists($path.\DIRECTORY_SEPARATOR.$file)) {
            $chunks = explode(\DIRECTORY_SEPARATOR, $path);

            array_pop($chunks);
            if (!$path) {
                throw new \Exception($file.' not found in hierarchy of directory.');
            }

            $path = implode(\DIRECTORY_SEPARATOR, $chunks);
            echo $path.\PHP_EOL;
            self::getBasePathForFile($file, $path);
        }

        return $path;
    }

    private function getSession(?string $name = null): Session
    {
        return $this->getMink()->getSession($name ?? $this->defaultSession);
    }

    /**
     * @param array<string, mixed> $debugBarSelectors
     */
    private function gatherFacts(
        Session $session,
        DriverInterface $driver,
        array $debugBarSelectors,
        ?string $featureFile,
        ?string $exceptionFile,
        ?ScenarioScope $scenario,
    ): string {
        $message = null;
        $driver = $session->getDriver();

        $currentUrl = null;
        if ($this->staticCaller->call(Output::class, 'getOption', ['url'])) {
            try {
                $currentUrl = $session->getCurrentUrl();
            } catch (\Exception $e) {
                $currentUrl = 'Unable to fetch current url, error: '.$e->getMessage();
            }
        }

        $statusCode = null;
        if ($this->staticCaller->call(Output::class, 'getOption', ['status'])) {
            try {
                $statusCode = $session->getStatusCode();
            } catch (DriverException $e) {
                $statusCode = 'Unable to fetch status code, error: '.$e->getMessage();
            }
        }

        $screenshotPath = null;
        if ($this->staticCaller->call(Output::class, 'getOption', ['screenshot'])) {
            try {
                $this->staticCaller->call(Screenshot::class, 'canTakeScreenshot', [$session]);
                $screenshotPath = $this->staticCaller->call(Screenshot::class, 'takeScreenshot', [
                    $session->getPage(),
                    $driver,
                ]);
            } catch (\Exception $e) {
                $screenshotPath = 'Unable to produce screenshot: '.$e->getMessage();
            }
        }

        $debugBarDetails = '';
        if ($this->staticCaller->call(Output::class, 'getOption', ['debugBarSelectors'])) {
            if ($debugBarSelectors) {
                try {
                    $debugBarDetails = $this->gatherDebugBarDetails(
                        $debugBarSelectors,
                        $session->getPage()
                    );
                } catch (\Exception $e) {
                    $debugBarDetails = 'Unable to capture debug bar details: '.$e->getMessage();
                }
            }
        }

        $jsErrors = $this->staticCaller->call(JSDebug::class, 'getJsErrors', [$session]);
        $jsWarns = $this->staticCaller->call(JSDebug::class, 'getJsWarns', [$session]);
        $jsLogs = $this->staticCaller->call(JSDebug::class, 'getJsLogs', [$session]);

        $message = $this->staticCaller->call(Output::class, 'getExceptionDetails', [
            $currentUrl,
            $statusCode,
            $featureFile,
            $exceptionFile,
            $screenshotPath,
            $debugBarDetails,
            $jsErrors,
            $jsLogs,
            $jsWarns,
            $driver::class,
            $scenario,
        ]);

        return (string) $message;
    }

    public function setMink(Mink $mink): void
    {
        $this->mink = $mink;
    }

    public function setMinkParameters(array $parameters): void
    {
        $this->minkParameters = $parameters;
    }

    public function getMink(): Mink
    {
        return $this->mink;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMinkParameters(): array
    {
        return $this->minkParameters;
    }

    /**
     * @param string|int $value
     */
    public static function addState(string $name, $value): void
    {
        self::$states[$name] = $value;
    }

    public static function getState(string $name, $default = null)
    {
        return isset(self::$states[$name]) ? self::$states[$name] : $default;
    }

    /**
     * @param array<string, mixed> $debugBarSelectors
     */
    public function gatherDebugBarDetails(array $debugBarSelectors, DocumentElement $page): string
    {
        $details = '';
        foreach ($debugBarSelectors as $name => $selector) {
            $details .= '  ['.strtoupper($name).'] ';
            if (\is_array($selector)) {
                if (!isset($selector['callback'])) {
                    throw new \Exception('Debug bar selector if array must have callback specified.');
                }
                [$class, $method] = explode('::', $selector['callback'], 2);
                $details .= $class::$method($page);
            } elseif ($detailText = $page->find('css', $selector)) {
                $details .= $detailText->getText();
            } else {
                $details .= 'Element "'.$selector.'" Not Found.';
            }
            $details .= \PHP_EOL;
        }

        return $details;
    }

    /**
     * @param array<string, mixed> $states
     */
    public function getStateDetails(array $states): string
    {
        $stateDetails = '';
        foreach ($states as $stateName => $stateValue) {
            $stateDetails .= '  ['.strtoupper($stateName).'] '.$stateValue.\PHP_EOL;
        }

        return $stateDetails;
    }

    private function addStateDetails(?string $message, string $stateDetails): string
    {
        if ($stateDetails) {
            $message .= \PHP_EOL.'[STATE]'.\PHP_EOL;
            $message .= $stateDetails;
        }

        $message .= \PHP_EOL;

        return (string) $message;
    }

    private function setAdditionalExceptionDetailsInException(\Throwable $exception, ?string $message): void
    {
        $reflectionObject = new \ReflectionObject($exception);
        $reflectionObjectProp = $reflectionObject->getProperty('message');
        $reflectionObjectProp->setValue($exception, $exception->getMessage().$message);
    }
}
