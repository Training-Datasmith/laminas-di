<?php

declare (strict_types=1);
namespace Laminas\Di\Resolver;

use const E_USER_DEPRECATED;
use function is_array;
use function is_object;
use function is_scalar;
use Laminas\Di\Exception\LogicException;
use function method_exists;
use Psr\Container\Container_Interface;
use ReflectionMethod;
use function trigger_error;
use function var_export;
/**
 * Wrapper for values that should be directly injected
 *
 * @final
 */
class Value_Injection implements Injection_Interface
{
    public function __construct(
        /**
         * Holds the value to inject
         */
        protected mixed $value
    )
    {
    }
    public static function __set_state(array $state): self
    {
        return new self($state['value']);
    }
    /**
     * Exports the encapsulated value to php code
     *
     * @throws LogicException
     */
    public function export(): string
    {
        if (!$this->is_exportable()) {
            throw new LogicException('Unable to export value');
        }
        if ($this->value === null) {
            return 'null';
        }
        return var_export($this->value, true);
    }
    /**
     * Checks wether the value can be exported for code generation or not
     */
    public function is_exportable(): bool
    {
        return $this->is_exportable_recursive($this->value);
    }
    /**
     * Check if the provided value is exportable.
     * For arrays it uses recursion.
     */
    private function is_exportable_recursive(mixed $value): bool
    {
        if (is_scalar($value) || $value === null) {
            return true;
        }
        if (is_array($value)) {
            /** @var mixed $item */
            foreach ($value as $item) {
                if (!$this->is_exportable_recursive($item)) {
                    return false;
                }
            }
            return true;
        }
        if (is_object($value) && method_exists($value, '__set_state')) {
            $method = new ReflectionMethod($value, '__set_state');
            return $method->is_static() && $method->is_public();
        }
        return false;
    }
    /** @return mixed */
    public function to_value(Container_Interface $container)
    {
        return $this->value;
    }
    /**
     * Get the value to inject
     *
     * @deprecated Since 3.1.0
     *
     * @see toValue()
     *
     * @return mixed
     */
    public function get_value()
    {
        trigger_error(__METHOD__ . ' is deprecated, please migrate to ' . self::class . '::toValue().', E_USER_DEPRECATED);
        return $this->value;
    }
}