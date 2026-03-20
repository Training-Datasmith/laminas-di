<?php

declare (strict_types=1);
namespace Laminas\Di\Code_Generator;

use Laminas\Di\Default_Container;
use Laminas\Di\Injector_Interface;
use Psr\Container\Container_Interface;
/**
 * Abstract class for code generated dependency injectors
 *
 * @deprecated Since 3.16.0, the code generator will be replaced by a separate package in version 4.0
 */
abstract class Abstract_Injector implements Injector_Interface
{
    /** @var array<string, class-string<FactoryInterface>|FactoryInterface> */
    protected $factories = [];
    /** @var array<string, FactoryInterface> */
    private array $factory_instances = [];
    private readonly Container_Interface $container;
    public function __construct(private readonly Injector_Interface $injector, ?Container_Interface $container = null)
    {
        $this->container = $container ?: new Default_Container($this);
        $this->load_factory_list();
    }
    /**
     * Init factory list
     */
    abstract protected function load_factory_list(): void;
    private function set_factory(string $type, Factory_Interface $factory): void
    {
        $this->factory_instances[$type] = $factory;
    }
    /**
     * @template T
     * @param string|class-string<T> $type
     * @return FactoryInterface<T>
     */
    private function get_factory(string $type): Factory_Interface
    {
        if (isset($this->factory_instances[$type])) {
            return $this->factory_instances[$type];
        }
        $factory_class = $this->factories[$type];
        $factory = $factory_class instanceof Factory_Interface ? $factory_class : new $factory_class();
        $this->set_factory($type, $factory);
        return $factory;
    }
    public function can_create(string $name): bool
    {
        if ($this->has_factory($name)) {
            return true;
        }
        return $this->injector->can_create($name);
    }
    private function has_factory(string $name): bool
    {
        return isset($this->factories[$name]);
    }
    /**
     * @template T of object
     * @param string|class-string<T> $name
     * @param array<mixed> $options
     * @return T
     */
    public function create(string $name, array $options = [])
    {
        if ($this->has_factory($name)) {
            return $this->get_factory($name)->create($this->container, $options);
        }
        return $this->injector->create($name, $options);
    }
}