<?php

// phpcs:ignoreFile
declare (strict_types=1);
namespace Laminas\Di\Resolver;

use const E_USER_DEPRECATED;
use function sprintf;
use function trigger_error;
trigger_error(sprintf('%s is deprecated, please migrate to %s', Abstract_Injection::class, Injection_Interface::class), E_USER_DEPRECATED);
/**
 * @deprecated Since 3.1.0
 *
 * @see InjectionInterface
 *
 * @codeCoverageIgnore Deprecated
 */
abstract class Abstract_Injection
{
    private string $parameter_name;
    public function set_parameter_name(string $name): self
    {
        $this->parameter_name = $name;
        return $this;
    }
    public function get_parameter_name(): string
    {
        return $this->parameter_name;
    }
    abstract public function export(): string;
    abstract public function is_exportable(): bool;
}