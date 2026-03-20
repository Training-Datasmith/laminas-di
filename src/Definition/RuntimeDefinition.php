<?php

declare (strict_types=1);
namespace Laminas\Di\Definition;

use function array_keys;
use function array_merge;
use function class_exists;
use Laminas\Di\Definition\Reflection\Class_Definition;
use Laminas\Di\Exception;
/**
 * Class definitions based on runtime reflection
 *
 * @final
 */
class Runtime_Definition implements Definition_Interface
{
    /** @var array<class-string, ClassDefinition> */
    private array $definition = [];
    /** @var array<class-string, bool> */
    private array $explicit_classes;
    /**
     * @param null|class-string[] $explicitClasses
     */
    public function __construct(?array $explicit_classes = null)
    {
        $this->explicit_classes = [];
        $this->set_explicit_classes($explicit_classes ?? []);
    }
    /**
     * Set explicit class names
     *
     * @see addExplicitClass()
     *
     * @param class-string[] $explicitClasses An array of class names
     * @throws Exception\ClassNotFoundException
     */
    public function set_explicit_classes(array $explicit_classes): self
    {
        $this->explicit_classes = [];
        foreach ($explicit_classes as $class) {
            $this->add_explicit_class($class);
        }
        return $this;
    }
    /**
     * Add class name explicitly
     *
     * Adding classes this way will cause the defintion to report them when getClasses()
     * is called, even when they're not yet loaded.
     *
     * @param class-string $class
     * @throws Exception\ClassNotFoundException
     */
    public function add_explicit_class(string $class): self
    {
        $this->ensure_class_exists($class);
        $this->explicit_classes[$class] = true;
        return $this;
    }
    /**
     * @psalm-assert class-string $class
     * @throws Exception\ClassNotFoundException
     */
    private function ensure_class_exists(string $class): void
    {
        if (!$this->has_class($class)) {
            throw new Exception\Class_Not_Found_Exception($class);
        }
    }
    /**
     * @param class-string $class The class name to load
     * @throws Exception\ClassNotFoundException
     */
    private function load_class(string $class): void
    {
        $this->ensure_class_exists($class);
        $this->definition[$class] = new Class_Definition($class);
    }
    /** @return list<class-string> */
    public function get_classes(): array
    {
        return array_keys(array_merge($this->definition, $this->explicit_classes));
    }
    /**
     * @psalm-assert-if-true class-string $class
     */
    public function has_class(string $class): bool
    {
        return class_exists($class);
    }
    /** @throws Exception\ClassNotFoundException */
    public function get_class_definition(string $class): Class_Definition_Interface
    {
        if (!isset($this->definition[$class])) {
            $this->load_class($class);
        }
        return $this->definition[$class];
    }
}