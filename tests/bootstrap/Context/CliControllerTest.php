<?php

declare(strict_types=1);

namespace FailAid\Tests\Context;

use FailAid\Context\ClearScreenshots;
use FailAid\Context\FailureContext;
use FailAid\Context\FeedbackOnFailure;
use FailAid\Context\ScenarioDebugCli;
use FailAid\Context\WaitOnFailure;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;

class CliControllerTest extends TestCase
{
    private static function readFailureContextProperty(string $name): mixed
    {
        $ref = new \ReflectionClass(FailureContext::class);
        $prop = $ref->getProperty($name);

        return $prop->getValue();
    }

    private static function resetFailureContextProperty(string $name, mixed $value): void
    {
        $ref = new \ReflectionClass(FailureContext::class);
        $prop = $ref->getProperty($name);
        $prop->setValue(null, $value);
    }

    private function makeInput(string $optionName, mixed $value, int $mode = InputOption::VALUE_NONE): ArrayInput
    {
        $definition = new InputDefinition([new InputOption($optionName, null, $mode)]);

        return new ArrayInput(['--' . $optionName => $value], $definition);
    }

    private function makeEmptyInput(string $optionName, int $mode = InputOption::VALUE_NONE): ArrayInput
    {
        $definition = new InputDefinition([new InputOption($optionName, null, $mode)]);

        return new ArrayInput([], $definition);
    }

    // --- ScenarioDebugCli ---

    public function test_scenario_debug_cli_configure_adds_option(): void
    {
        $command = new SymfonyCommand('behat');
        (new ScenarioDebugCli())->configure($command);

        $this->assertTrue($command->getDefinition()->hasOption('scenario-debug'));
    }

    public function test_scenario_debug_cli_execute_returns_null_when_option_absent(): void
    {
        $controller = new ScenarioDebugCli();
        $input = $this->makeEmptyInput('scenario-debug');
        $result = $controller->execute($input, new NullOutput());

        $this->assertNull($result);
    }

    public function test_scenario_debug_cli_execute_returns_null_and_sets_debug_scenario(): void
    {
        self::resetFailureContextProperty('debugScenario', false);

        $controller = new ScenarioDebugCli();
        $input = $this->makeInput('scenario-debug', true);
        $result = $controller->execute($input, new NullOutput());

        $this->assertNull($result);
        $this->assertTrue(self::readFailureContextProperty('debugScenario'));

        self::resetFailureContextProperty('debugScenario', false);
    }

    // --- ClearScreenshots ---

    public function test_clear_screenshots_configure_adds_option(): void
    {
        $command = new SymfonyCommand('behat');
        (new ClearScreenshots())->configure($command);

        $this->assertTrue($command->getDefinition()->hasOption('clear-screenshots'));
    }

    public function test_clear_screenshots_execute_returns_null_when_option_absent(): void
    {
        $controller = new ClearScreenshots();
        $input = $this->makeEmptyInput('clear-screenshots');
        $result = $controller->execute($input, new NullOutput());

        $this->assertNull($result);
    }

    public function test_clear_screenshots_execute_returns_null_and_sets_auto_clean(): void
    {
        self::resetFailureContextProperty('autoClean', false);

        $controller = new ClearScreenshots();
        $input = $this->makeInput('clear-screenshots', true);
        $result = $controller->execute($input, new NullOutput());

        $this->assertNull($result);
        $this->assertTrue(self::readFailureContextProperty('autoClean'));

        self::resetFailureContextProperty('autoClean', false);
    }

    // --- WaitOnFailure ---

    public function test_wait_on_failure_configure_adds_option(): void
    {
        $command = new SymfonyCommand('behat');
        (new WaitOnFailure())->configure($command);

        $this->assertTrue($command->getDefinition()->hasOption('wait-on-failure'));
    }

    public function test_wait_on_failure_execute_returns_null_when_option_absent(): void
    {
        $controller = new WaitOnFailure();
        $input = $this->makeEmptyInput('wait-on-failure', InputOption::VALUE_REQUIRED);
        $result = $controller->execute($input, new NullOutput());

        $this->assertNull($result);
    }

    public function test_wait_on_failure_execute_returns_null_and_sets_wait_time(): void
    {
        self::resetFailureContextProperty('waitOnFailure', 0);

        $controller = new WaitOnFailure();
        $input = $this->makeInput('wait-on-failure', '5', InputOption::VALUE_REQUIRED);
        $result = $controller->execute($input, new NullOutput());

        $this->assertNull($result);
        $this->assertSame(5, self::readFailureContextProperty('waitOnFailure'));

        self::resetFailureContextProperty('waitOnFailure', 0);
    }

    // --- FeedbackOnFailure ---

    public function test_feedback_on_failure_configure_adds_option(): void
    {
        $command = new SymfonyCommand('behat');
        (new FeedbackOnFailure())->configure($command);

        $this->assertTrue($command->getDefinition()->hasOption('feedback-on-failure'));
    }

    public function test_feedback_on_failure_execute_returns_null_when_option_absent(): void
    {
        $controller = new FeedbackOnFailure();
        $input = $this->makeEmptyInput('feedback-on-failure');
        $result = $controller->execute($input, new NullOutput());

        $this->assertNull($result);
    }

    public function test_feedback_on_failure_execute_returns_null_and_sets_feedback(): void
    {
        self::resetFailureContextProperty('feedbackOnFailure', false);

        $controller = new FeedbackOnFailure();
        $input = $this->makeInput('feedback-on-failure', true);
        $result = $controller->execute($input, new NullOutput());

        $this->assertNull($result);
        $this->assertTrue(self::readFailureContextProperty('feedbackOnFailure'));

        self::resetFailureContextProperty('feedbackOnFailure', false);
    }
}
