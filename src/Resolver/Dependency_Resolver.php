<?php

declare (strict_types=1);
namespace Laminas\Di\Resolver;

use function array_filter;
use function array_merge;
use function assert;
use function class_exists;
use function gettype;
use function in_array;
use function interface_exists;
use function is_callable;
use function is_iterable;
use function is_numeric;
use function is_string;
use Laminas\Di\Definition\Class_Definition_Interface;
use Laminas\Di\Exception;
use Psr\Container\Container_Interface;
use ReflectionClass;
use function sprintf;
/**
 * The default resolver implementation
 *
 * @final
 */
class Dependency_Resolver implements Dependency_Resolver_Interface
{
    /** @var ContainerInterface|null */
    protected $container;
    /** @var string[] */
    private array $builtin_types = ['string', 'int', 'bool', 'float', 'double', 'array', 'resource', 'callable', 'iterable'];
    /** @var array<string, string> */
    private array $gettype_map = ['boolean' => 'bool', 'integer' => 'int', 'double' => 'float'];
    public function __construct(protected \Laminas\Di\Definition\Definition_Interface $definition, protected \Laminas\Di\Config_Interface $config)
    {
    }
    private function get_class_definition(string $type): Class_Definition_Interface
    {
        if ($this->config->is_alias($type)) {
            $type = $this->config->get_class_for_alias($type) ?? $type;
        }
        return $this->definition->get_class_definition($type);
    }
    /**
     * Returns the configured injections for the requested type
     *
     * If type is an alias it will try to fall back to the class configuration if no parameters
     * were defined for it
     *
     * @param string $requestedType The type name to get injections for
     * @return array Injections for the method indexed by the parameter name
     */
    private function get_configured_parameters(string $requested_type): array
    {
        $config = $this->config;
        $params = $config->get_parameters($requested_type);
        $is_alias = $config->is_alias($requested_type);
        $class = $is_alias ? $config->get_class_for_alias($requested_type) ?? $requested_type : $requested_type;
        if ($is_alias) {
            $params = array_merge($config->get_parameters($class), $params);
        }
        $definition = $this->get_class_definition($class);
        foreach ($definition->get_supertypes() as $supertype) {
            $supertype_params = $config->get_parameters($supertype);
            if (!empty($supertype_params)) {
                $params = array_merge($supertype_params, $params);
            }
        }
        // A type configuration may define a parameter should be auto resolved
        // even it was defined earlier
        $params = array_filter($params, static fn($value): bool => $value !== '*');
        return $params;
    }
    /**
     * Check if $type satisfies $requiredType
     *
     * @param string $type The type to check
     * @param string $requiredType The required type to check against
     */
    private function is_type_of(string $type, string $required_type): bool
    {
        if ($this->config->is_alias($type)) {
            $type = $this->config->get_class_for_alias($type) ?? $type;
        }
        if ($type === $required_type) {
            return true;
        }
        if (interface_exists($type) && interface_exists($required_type)) {
            $reflection = new ReflectionClass($type);
            return in_array($required_type, $reflection->get_interface_names());
        }
        if (!$this->definition->has_class($type)) {
            return false;
        }
        $definition = $this->definition->get_class_definition($type);
        return in_array($required_type, $definition->get_supertypes()) || in_array($required_type, $definition->get_interfaces());
    }
    private function is_usable_type(string $type, string $required_type): bool
    {
        return $this->is_type_of($type, $required_type) && ($this->container === null || $this->container->has($type));
    }
    private function get_type_name_from_value(mixed $value): string
    {
        $type = gettype($value);
        return $this->gettype_map[$type] ?? $type;
    }
    /**
     * Check if the given value sadisfies the given type
     *
     * @param mixed  $value The value to check
     * @param string $type The typename to check against
     */
    private function is_value_of(mixed $value, string $type): bool
    {
        if (!$this->is_builtin_type($type)) {
            return $value instanceof $type;
        }
        if ($type === 'callable') {
            return is_callable($value);
        }
        if ($type === 'iterable') {
            return is_iterable($value);
        }
        $value_type = $this->get_type_name_from_value($value);
        $numerics = ['int', 'float'];
        // PHP accepts float for int and vice versa, as well as numeric string values
        if (in_array($type, $numerics)) {
            return in_array($value_type, $numerics) || is_string($value) && is_numeric($value);
        }
        return $type === $value_type;
    }
    private function is_builtin_type(string $type): bool
    {
        return in_array($type, $this->builtin_types);
    }
    /**
     * @see DependencyResolverInterface::setContainer()
     *
     * @return $this
     */
    public function set_container(Container_Interface $container): static
    {
        $this->container = $container;
        return $this;
    }
    private function is_callable_type(string $type): bool
    {
        if ($this->config->is_alias($type)) {
            $type = $this->config->get_class_for_alias($type) ?? $type;
        }
        if (!class_exists($type) && !interface_exists($type)) {
            return false;
        }
        $reflection = new ReflectionClass($type);
        return $reflection->has_method('__invoke') && $reflection->get_method('__invoke')->is_public();
    }
    /**
     * Prepare a candidate for injection
     *
     * If the candidate is usable, its injection representation is returned
     */
    private function prepare_injection(mixed $value, ?string $required_type): ?Injection_Interface
    {
        if ($value instanceof Value_Injection || $value instanceof Type_Injection) {
            return $value;
        }
        if (!$required_type) {
            $is_available_in_container = is_string($value) && $this->container !== null && $this->container->has($value);
            return $is_available_in_container ? new Type_Injection($value) : new Value_Injection($value);
        }
        if (is_string($value) && !$this->is_builtin_type($required_type)) {
            return new Type_Injection($value);
        }
        // Classes may implement iterable
        if (is_string($value) && $required_type === 'iterable') {
            return $this->is_usable_type($value, 'Traversable') ? new Type_Injection($value) : null;
        }
        // Classes may implement callable, but strings could be callable as well
        if (is_string($value) && $required_type === 'callable' && $this->is_callable_type($value)) {
            return new Type_Injection($value);
        }
        return $this->is_value_of($value, $required_type) ? new Value_Injection($value) : null;
    }
    /**
     * {@inheritDoc}
     *
     * @see DependencyResolverInterface::resolveParameters()
     *
     * @throws Exception\UnexpectedValueException
     * @throws Exception\MissingPropertyException
     * @return InjectionInterface[]
     */
    public function resolve_parameters(string $requested_type, array $call_time_parameters = []): array
    {
        $definition = $this->get_class_definition($requested_type);
        $params = $definition->get_parameters();
        $result = [];
        if (empty($params)) {
            return $result;
        }
        $configured_parameters = $this->get_configured_parameters($requested_type);
        foreach ($params as $param_info) {
            $name = $param_info->get_name();
            $type = $param_info->get_type();
            if (isset($call_time_parameters[$name])) {
                $result[$name] = new Value_Injection($call_time_parameters[$name]);
                continue;
            }
            if (isset($configured_parameters[$name]) && $configured_parameters[$name] !== '*') {
                $injection = $this->prepare_injection($configured_parameters[$name], $type);
                if (!$injection) {
                    throw new Exception\UnexpectedValueException(sprintf('Unusable configured injection for parameter "%s" of type "%s"', $name, $type ?? 'null'));
                }
                $result[$name] = $injection;
                continue;
            }
            if ($type && !$param_info->is_builtin()) {
                $preference = $this->resolve_preference($type, $requested_type);
                if ($preference) {
                    $result[$name] = new Type_Injection($preference);
                    continue;
                }
                if ($type === Container_Interface::class || $this->container === null || $this->container->has($type)) {
                    $result[$name] = new Type_Injection($type);
                    continue;
                }
            }
            // The parameter is required, but we can't find anything that is suitable
            if ($param_info->is_required()) {
                $is_alias = $this->config->is_alias($requested_type);
                $class = $is_alias ? $this->config->get_class_for_alias($requested_type) : $requested_type;
                assert(is_string($class));
                throw new Exception\Missing_Property_Exception(sprintf('Could not resolve value for parameter "%s" of type %s in class %s (requested as %s)', $name, $type ?: 'any', $class, $requested_type));
            }
            $result[$name] = new Value_Injection($param_info->get_default());
        }
        return $result;
    }
    /**
     * @see DependencyResolverInterface::resolvePreference()
     */
    public function resolve_preference(string $type, ?string $context = null): ?string
    {
        if ($context) {
            $preference = $this->config->get_type_preference($type, $context);
            if ($preference && $this->is_usable_type($preference, $type)) {
                return $preference;
            }
            $definition = $this->get_class_definition($context);
            foreach ($definition->get_supertypes() as $supertype) {
                $preference = $this->config->get_type_preference($type, $supertype);
                if ($preference && $this->is_usable_type($preference, $type)) {
                    return $preference;
                }
            }
            foreach ($definition->get_interfaces() as $interface) {
                $preference = $this->config->get_type_preference($type, $interface);
                if ($preference && $this->is_usable_type($preference, $type)) {
                    return $preference;
                }
            }
        }
        $preference = $this->config->get_type_preference($type);
        if (!$preference || !$this->is_usable_type($preference, $type)) {
            return null;
        }
        return $preference;
    }
}