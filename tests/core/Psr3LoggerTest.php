<?php
/**
 * Tests for PSR-3 Logger compliance (Phase 16)
 */

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Gyro\Lib\Components\LoggerInstance;
use PHPUnit\Framework\TestCase;

class Psr3LoggerTest extends TestCase
{
	private LoggerInstance $logger;
	private string $logDir;

	protected function setUp(): void
	{
		$this->logDir = sys_get_temp_dir() . '/gyro_psr3_test_' . uniqid() . '/';
		mkdir($this->logDir, 0777, true);
		Config::set_value(Config::LOG_DIR, $this->logDir);
		Config::set_value(Config::LOG_FILE_NAME_PATTERN, '%date%_%name%.log');
		Logger::set_min_level(Logger::DEBUG);
		$this->logger = new LoggerInstance();
	}

	protected function tearDown(): void
	{
		// Clean up test log files
		$files = glob($this->logDir . '*');
		if ($files) {
			foreach ($files as $file) {
				unlink($file);
			}
		}
		if (is_dir($this->logDir)) {
			rmdir($this->logDir);
		}
	}

	public function test_implements_psr3_interface(): void
	{
		$this->assertInstanceOf(LoggerInterface::class, $this->logger);
	}

	public function test_emergency(): void
	{
		$this->logger->emergency('System is down');
		$this->assertLogFileContains('emergency', 'System is down');
	}

	public function test_alert(): void
	{
		$this->logger->alert('Action required');
		$this->assertLogFileContains('alert', 'Action required');
	}

	public function test_critical(): void
	{
		$this->logger->critical('Critical failure');
		$this->assertLogFileContains('critical', 'Critical failure');
	}

	public function test_error(): void
	{
		$this->logger->error('An error occurred');
		$this->assertLogFileContains('error', 'An error occurred');
	}

	public function test_warning(): void
	{
		$this->logger->warning('Something might be wrong');
		$this->assertLogFileContains('warning', 'Something might be wrong');
	}

	public function test_notice(): void
	{
		$this->logger->notice('Normal but significant');
		$this->assertLogFileContains('notice', 'Normal but significant');
	}

	public function test_info(): void
	{
		$this->logger->info('Informational message');
		$this->assertLogFileContains('info', 'Informational message');
	}

	public function test_debug(): void
	{
		$this->logger->debug('Debug details');
		$this->assertLogFileContains('debug', 'Debug details');
	}

	public function test_log_with_psr3_level_constants(): void
	{
		$this->logger->log(LogLevel::ERROR, 'PSR-3 level error');
		$this->assertLogFileContains('error', 'PSR-3 level error');
	}

	public function test_log_with_all_psr3_levels(): void
	{
		$levels = [
			LogLevel::EMERGENCY,
			LogLevel::ALERT,
			LogLevel::CRITICAL,
			LogLevel::ERROR,
			LogLevel::WARNING,
			LogLevel::NOTICE,
			LogLevel::INFO,
			LogLevel::DEBUG,
		];
		foreach ($levels as $level) {
			$this->logger->log($level, "Test $level");
			$this->assertLogFileContains($level, "Test $level");
		}
	}

	public function test_context_interpolation(): void
	{
		$this->logger->error('User {user} failed login from {ip}', [
			'user' => 'admin',
			'ip' => '192.168.1.1',
		]);
		$this->assertLogFileContains('error', 'User admin failed login from 192.168.1.1');
	}

	public function test_exception_in_context(): void
	{
		$exception = new \RuntimeException('Test exception', 42);
		$this->logger->error('Something failed', ['exception' => $exception]);
		$content = $this->getLogFileContent('error');
		$this->assertNotEmpty($content);
		$entry = json_decode($content, true);
		$this->assertArrayHasKey('exception', $entry);
		$this->assertEquals('RuntimeException', $entry['exception']['class']);
		$this->assertEquals('Test exception', $entry['exception']['message']);
		$this->assertEquals(42, $entry['exception']['code']);
	}

	public function test_stringable_message(): void
	{
		$stringable = new class {
			public function __toString(): string
			{
				return 'stringable message';
			}
		};
		$this->logger->info($stringable);
		$this->assertLogFileContains('info', 'stringable message');
	}

	private function assertLogFileContains(string $level, string $expected): void
	{
		$content = $this->getLogFileContent($level);
		$this->assertStringContainsString($expected, $content,
			"Log file for level '$level' should contain '$expected'");
	}

	private function getLogFileContent(string $level): string
	{
		$file = $this->logDir . date('Y-m-d') . '_' . $level . '.log';
		if (!file_exists($file)) {
			return '';
		}
		return file_get_contents($file);
	}
}
