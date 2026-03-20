<?php

declare (strict_types=1);
namespace Laminas\Di;

use function array_filter;
use function array_keys;
use function array_map;
use ArrayAccess;
use function class_exists;
use function interface_exists;
use function is_array;
use function is_string;
/**
 * Provides a DI configuration from an array.
 *
 * This configures the instantiation process of the dependency injector.
 *
 * **Example:**
 *
 * <code>
 * return [
 *     // This section provides global type preferences.
 *     // Those are visited if a specific instance has no preference definitions.
 *     'preferences' => [
 *         // The key is the requested class or interface name, the values are
 *         // the types the dependency injector should prefer.
 *         Some\Interface::class => Some\Preference::class
 *     ],
 *     // This configures the instantiation of specific types.
 *     // Types may also be purely virtual by defining the aliasOf key.
 *     'types' => [
 *         My\Class::class => [
 *              'preferences' => [
 *                  // this supercedes the global type preferences
 *                  // when My\Class is instantiated
 *                  Some\Interface::class => 'My.SpecificAlias'
 *              ],
 *
 *              // instantiation parameters. These will only be used for
 *              // the instantiator (i.e. the constructor)
 *              'parameters' => [
 *                  'foo' => My\FooImpl::class, // Use the given type to provide the injection (depends on definition)
 *                  'bar' => '*' // Use the type preferences
 *              ],
 *         ],
 *
 *         'My.Alias' => [
 *             // typeOf defines virtual classes which can be used as type
 *             // preferences or for newInstance calls. They allow providing
 *             // custom configs for a class
 *             'typeOf' => Some\Class::class,
 *             'preferences' => [
 *                  Foo::class => Bar::class
 *             ]
 *         ]
 *     ]
 * ];
 * </code>
 *
 * ## Notes on Injections
 *
 * Named arguments and Automatic type lookups will only work for Methods that
 * are known to the dependency injector through its definitions. Injections for
 * unknown methods do not perform type lookups on its own.
 *
 * A value injection without any lookups can be forced by providing a
 * Resolver\ValueInjection instance.
 *
 * To force a service/class instance provide a Resolver\TypeInjection instance.
 * For classes known from the definitions, a type preference might be the
 * better approach
 *
 * @see \Laminas\Di\Resolver\ValueInjection A container to force injection of a value
 * @see \Laminas\Di\Resolver\TypeInjection  A container to force looking up a specific type instance for injection
 *
 * @final
 *
 * @psalm-type TypeConfigArray = array{
 *  typeOf?: class-string|null,
 *  preferences?: array<string, string>|null,
 *  parameters?: array<string, mixed>|null
 * }
 */
class Config implements Config_Interface
{
    /** @var array */
    protected $preferences = [];
    /** @var array<array> */
    protected array $types;
    /**
     * Construct from options array
     *
     * Utilizes the given options array or traversable.
     *
     * @param array<mixed>|ArrayAccess<mixed, mixed> $options The options array.
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($options = [])
    {
        $this->ensure_array_or_array_access($options);
        $this->preferences = $this->get_data_from_array($options, 'preferences');
        /** @psalm-var array<array> Psalm does not catch the array filter with type predicate */
        $this->types = array_filter($this->get_data_from_array($options, 'types'), is_array(...));
    }
    private function get_data_from_array(\ArrayAccess|array $data, string $key): array
    {
        /** @var mixed $result */
        $result = $data[$key] ?? [];
        return is_array($result) ? $result : [];
    }
    /**
     * {@inheritDoc}
     *
     * @see \Laminas\Di\ConfigInterface::getClassForAlias()
     */
    public function get_class_for_alias(string $name): ?string
    {
        if (isset($this->types[$name]['typeOf']) && is_string($this->types[$name]['typeOf']) && (class_exists($this->types[$name]['typeOf']) || interface_exists($this->types[$name]['typeOf']))) {
            return $this->types[$name]['typeOf'];
        }
        return null;
    }
    /**
     * Returns the instantiation parameters for the given type
     *
     * @param string $type The alias or class name
     * @return array<mixed> The configured parameters
     */
    public function get_parameters(string $type): array
    {
        if (!isset($this->types[$type]['parameters']) || !is_array($this->types[$type]['parameters'])) {
            return [];
        }
        return $this->types[$type]['parameters'];
    }
    /**
     * {@inheritDoc}
     *
     * @see \Laminas\Di\ConfigInterface::setParameters()
     *
     * @return $this
     * @param array<mixed> $params
     */
    public function set_parameters(string $type, array $params): static
    {
        $this->types[$type]['parameters'] = $params;
        return $this;
    }
    public function get_type_preference(string $type, ?string $context_class = null): ?string
    {
        if ($context_class) {
            return $this->get_type_preference_for_class($type, $context_class);
        }
        if (!isset($this->preferences[$type])) {
            return null;
        }
        /** @var mixed $preference */
        $preference = $this->preferences[$type];
        return $preference !== '' ? (string) $preference : null;
    }
    /**
     * {@inheritDoc}
     *
     * @see \Laminas\Di\ConfigInterface::getTypePreferencesForClass()
     */
    private function get_type_preference_for_class(string $type, string $context): ?string
    {
        if (!isset($this->types[$context]['preferences'][$type])) {
            return null;
        }
        /** @var mixed $preference */
        $preference = $this->types[$context]['preferences'][$type];
        return $preference !== '' ? (string) $preference : null;
    }
    /**
     * {@inheritDoc}
     *
     * @see ConfigInterface::isAlias()
     */
    public function is_alias(string $name): bool
    {
        return isset($this->types[$name]['typeOf']);
    }
    /**
     * {@inheritDoc}
     *
     * @see ConfigInterface::getConfiguredTypeNames()
     *
     * @return list<string>
     */
    public function get_configured_type_names(): array
    {
        return array_map(strval(...), array_keys($this->types));
    }
    public function set_type_preference(string $type, string $preference, ?string $context = null): self
    {
        if ($context) {
            /** @psalm-suppress MixedArrayAssignment TODO: Eliminate array structures with the next releases */
            $this->types[$context]['preferences'][$type] = $preference;
            return $this;
        }
        $this->preferences[$type] = $preference;
        return $this;
    }
    /**
     * @param string $name The name of the alias
     * @param string $class The class name this alias points to
     * @throws Exception\ClassNotFoundException When `$class` does not exist.
     */
    public function set_alias(string $name, string $class): self
    {
        if (!class_exists($class) && !interface_exists($class)) {
            throw new Exception\Class_Not_Found_Exception($class);
        }
        $this->types[$name]['typeOf'] = $class;
        return $this;
    }
    /**
     * @psalm-assert array|ArrayAccess $options
     */
    private function ensure_array_or_array_access(mixed $options): void
    {
        if (!is_array($options) && !$options instanceof ArrayAccess) {
            throw new Exception\InvalidArgumentException('Config data must be of type array or ArrayAccess');
        }
    }
}