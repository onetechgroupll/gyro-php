<?php
namespace Gyro\Lib\Components;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * PSR-3 compatible Logger instance
 *
 * Wraps the static Logger facade as an instance implementing PSR-3 LoggerInterface.
 * This allows the Gyro Logger to be used wherever a PSR-3 logger is expected.
 *
 * Usage:
 *   $logger = new \Gyro\Lib\Components\LoggerInstance();
 *   $logger->error('Something went wrong', ['exception' => $ex]);
 *
 *   // Or register in the DI container:
 *   Container::instance()->singleton('logger', function() {
 *       return new \Gyro\Lib\Components\LoggerInstance();
 *   });
 */
class LoggerInstance implements LoggerInterface
{
	/**
	 * Map PSR-3 LogLevel constants to Gyro Logger constants
	 */
	private const LEVEL_MAP = [
		LogLevel::EMERGENCY => \Logger::EMERGENCY,
		LogLevel::ALERT     => \Logger::ALERT,
		LogLevel::CRITICAL  => \Logger::CRITICAL,
		LogLevel::ERROR     => \Logger::ERROR,
		LogLevel::WARNING   => \Logger::WARNING,
		LogLevel::NOTICE    => \Logger::NOTICE,
		LogLevel::INFO      => \Logger::INFO,
		LogLevel::DEBUG     => \Logger::DEBUG,
	];

	public function emergency(string|\Stringable $message, array $context = []): void
	{
		\Logger::emergency((string)$message, $context);
	}

	public function alert(string|\Stringable $message, array $context = []): void
	{
		\Logger::alert((string)$message, $context);
	}

	public function critical(string|\Stringable $message, array $context = []): void
	{
		\Logger::critical((string)$message, $context);
	}

	public function error(string|\Stringable $message, array $context = []): void
	{
		\Logger::error((string)$message, $context);
	}

	public function warning(string|\Stringable $message, array $context = []): void
	{
		\Logger::warning((string)$message, $context);
	}

	public function notice(string|\Stringable $message, array $context = []): void
	{
		\Logger::notice((string)$message, $context);
	}

	public function info(string|\Stringable $message, array $context = []): void
	{
		\Logger::info((string)$message, $context);
	}

	public function debug(string|\Stringable $message, array $context = []): void
	{
		\Logger::debug((string)$message, $context);
	}

	public function log($level, string|\Stringable $message, array $context = []): void
	{
		$gyro_level = self::LEVEL_MAP[$level] ?? \Logger::DEBUG;
		\Logger::log_level($gyro_level, (string)$message, $context);
	}
}
