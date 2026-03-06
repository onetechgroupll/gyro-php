<?php
namespace Gyro\Lib\Components\Exception;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Exception thrown when a service is not found in the container.
 *
 * Implements PSR-11 NotFoundExceptionInterface for interoperability.
 */
class ServiceNotFoundException extends \Exception implements NotFoundExceptionInterface
{
}
