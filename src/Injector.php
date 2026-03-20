<?php

declare (strict_types=1);
namespace Laminas\Di;

use function array_pop;
use function class_exists;
use function implode;
use function in_array;
use Laminas\Di\Definition\Definition_Interface;
use Laminas\Di\Exception\Class_Not_Found_Exception;
use Laminas\Di\Exception\Invalid_Callback_Exception;
use Laminas\Di\Exception\RuntimeException;
use Laminas\Di\Resolver\Dependency_Resolver_Interface;
use Laminas\Di\Resolver\Injection_Interface;
use Laminas\Di\Resolver\Type_Injection;
use Psr\Container\Container_Interface;
use Psr\Container\Not_Found_Exception_Interface;
use function sprintf;
/**
 * Dependency injector that can generate instances using class definitions and configured instance parameters
 *
 * @final
 */
class Injector implements Injector_Interface
{
    protected \Laminas\Di\Definition\Definition_Interface $definition;
    /** @var ContainerInterface */
    protected $container;
    protected \Laminas\Di\Resolver\Dependency_Resolver_Interface $resolver;
    protected \Laminas\Di\Config_Interface $config;
    /** @var string[] */
    protected $instantiation_stack = [];
    /**
     * Constructor
     *
     * @param ConfigInterface|null             $config A custom configuration to utilize. An empty configuration is used
     *                  when null is passed or the parameter is omitted.
     * @param ContainerInterface|null          $container The IoC container to retrieve dependency instances.
     *               `Laminas\Di\DefaultContainer` is used when null is passed or the parameter is omitted.
     * @param null|DefinitionInterface         $definition A custom definition instance for creating requested
     *               instances. The runtime definition is used when null is passed or the parameter is omitted.
     * @param DependencyResolverInterface|null $resolver A custom resolver instance to resolve dependencies.
     *      The default resolver is used when null is passed or the parameter is omitted
     */
    public function __construct(?Config_Interface $config = null, ?Container_Interface $container = null, ?Definition_Interface $definition = null, ?Dependency_Resolver_Interface $resolver = null)
    {
        $this->definition = $definition ?: new Definition\Runtime_Definition();
        $this->config = $config ?: new Config();
        $this->resolver = $resolver ?: new Resolver\Dependency_Resolver($this->definition, $this->config);
        $this->set_container($container ?: new Default_Container($this));
    }
    /**
     * Set the ioc container
     *
     * Sets the ioc container to utilize for fetching instances of dependencies
     *
     * @return $this
     */
    public function set_container(Container_Interface $container): static
    {
        $this->resolver->set_container($container);
        $this->container = $container;
        return $this;
    }
    /**
     * Return the PSR-11 container used to retrieve pre-built dependency instances.
     *
     * @return Container_Interface The currently configured IoC container.
     */
    public function get_container(): Container_Interface
    {
        return $this->container;
    }
    /**
     * Returns the class name for the requested type
     */
    private function get_class_name(string $type): string
    {
        if ($this->config->is_alias($type)) {
            return $this->config->get_class_for_alias($type) ?? $type;
        }
        return $type;
    }
    /**
     * Check if the given type name can be instantiated
     *
     * This will be the case if the name points to a class.
     */
    public function can_create(string $name): bool
    {
        $class = $this->get_class_name($name);
        return class_exists($class);
    }
    /**
     * Create the instance with auto wiring
     *
     * @template T of object
     * @param string|class-string<T> $name Class name or service alias
     * @param array<mixed> $options Constructor parameters, keyed by the parameter name.
     * @return T
     * @throws ClassNotFoundException
     * @throws RuntimeException
     */
    public function create(string $name, array $options = [])
    {
        if (in_array($name, $this->instantiation_stack)) {
            throw new Exception\Circular_Dependency_Exception(sprintf('Circular dependency: %s -> %s', implode(' -> ', $this->instantiation_stack), $name));
        }
        $this->instantiation_stack[] = $name;
        try {
            $instance = $this->create_instance($name, $options);
        } finally {
            array_pop($this->instantiation_stack);
        }
        return $instance;
    }
    /**
     * Retrieve a class instance based on the type name
     *
     * Any parameters provided will be used as constructor arguments only.
     *
     * @template T of object
     * @param string|class-string<T> $name The type name to instantiate.
     * @param array<mixed> $params Constructor arguments, keyed by the parameter name.
     * @return T
     * @throws InvalidCallbackException
     * @throws ClassNotFoundException
     */
    protected function create_instance(string $name, array $params)
    {
        $class = $this->get_class_name($name);
        if (!$this->definition->has_class($class)) {
            $alias_msg = $name !== $class ? ' (specified by alias ' . $name . ')' : '';
            throw new Class_Not_Found_Exception(sprintf('Class %s%s could not be located in provided definitions.', $class, $alias_msg));
        }
        if (!class_exists($class)) {
            throw new Class_Not_Found_Exception(sprintf('Class by name %s does not exist', $class));
        }
        $call_parameters = $this->resolve_parameters($name, $params);
        /**
         * @psalm-suppress MixedMethodCall
         * @psalm-var T
         */
        return new $class(...$call_parameters);
    }
    /**
     * @return mixed The value to inject into the instance
     */
    private function get_injection_value(Injection_Interface $injection)
    {
        $container = $this->container;
        $container_types = [
            Container_Interface::class,
            // Be backwards compatible with interop/container:
            'Interop\Container\ContainerInterface',
        ];
        if ($injection instanceof Type_Injection && !$container->has((string) $injection) && in_array((string) $injection, $container_types, true)) {
            return $container;
        }
        return $injection->to_value($container);
    }
    /**
     * Resolve parameters
     *
     * At first this method utilizes the resolver to obtain the types to inject.
     * If this was successful (the resolver returned a non-null value), it will use
     * the ioc container to fetch the instances
     *
     * @param string                $type The class or alias name to resolve for
     * @param array<string, mixed>  $params Provided call time parameters
     * @return list<mixed> The resulting arguments in call order
     * @throws Exception\UndefinedReferenceException When a type cannot be
     *     obtained via the ioc container and the method is required for
     *     injection.
     * @throws Exception\CircularDependencyException When a circular dependency is detected.
     */
    private function resolve_parameters(string $type, array $params = []): array
    {
        $resolved = $this->resolver->resolve_parameters($type, $params);
        $found_params = [];
        foreach ($resolved as $injection) {
            try {
                $found_params[] = $this->get_injection_value($injection);
            } catch (Not_Found_Exception_Interface $container_exception) {
                throw new Exception\Undefined_Reference_Exception($container_exception->get_message(), (int) $container_exception->get_code(), $container_exception);
            }
        }
        return $found_params;
    }
}