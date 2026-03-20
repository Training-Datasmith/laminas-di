<?php

declare (strict_types=1);
namespace Laminas\Di\Definition\Reflection;

use Laminas\Di\Definition\Class_Definition_Interface;
use ReflectionClass;
/**
 * @final
 */
class Class_Definition implements Class_Definition_Interface
{
    private readonly ReflectionClass $reflection;
    /** @var array<string, Parameter>|null */
    private ?array $parameters = null;
    /** @var list<class-string>|null */
    private ?array $supertypes = null;
    /**
     * @param class-string|ReflectionClass $class
     */
    public function __construct($class)
    {
        if (!$class instanceof ReflectionClass) {
            $class = new ReflectionClass($class);
        }
        $this->reflection = $class;
    }
    /**
     * @psalm-assert list<string> $this->supertypes
     */
    private function reflect_supertypes(): void
    {
        $this->supertypes = [];
        $class = $this->reflection;
        while ($class = $class->get_parent_class()) {
            $this->supertypes[] = $class->name;
        }
    }
    public function get_reflection(): ReflectionClass
    {
        return $this->reflection;
    }
    /**
     * @return list<class-string>
     */
    public function get_supertypes(): array
    {
        if ($this->supertypes === null) {
            $this->reflect_supertypes();
        }
        return $this->supertypes;
    }
    /**
     * @return string[]
     */
    public function get_interfaces(): array
    {
        return $this->reflection->get_interface_names();
    }
    /**
     * @psalm-assert array<string, Parameter> $this->parameters
     */
    private function reflect_parameters(): void
    {
        $this->parameters = [];
        $constructor = $this->reflection->get_constructor();
        if ($constructor === null) {
            return;
        }
        foreach ($constructor->get_parameters() as $parameter_reflection) {
            $parameter = new Parameter($parameter_reflection);
            $this->parameters[$parameter->get_name()] = $parameter;
        }
    }
    /**
     * @return array<string, Parameter>
     */
    public function get_parameters(): array
    {
        if ($this->parameters === null) {
            $this->reflect_parameters();
        }
        return $this->parameters;
    }
}