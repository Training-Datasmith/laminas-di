<?php

declare (strict_types=1);
namespace Laminas\Di\Definition;

use ReflectionClass;
interface Class_Definition_Interface
{
    public function get_reflection(): ReflectionClass;
    /**
     * @return string[]
     */
    public function get_supertypes(): array;
    /**
     * @return string[]
     */
    public function get_interfaces(): array;
    /**
     * @return ParameterInterface[]
     */
    public function get_parameters(): array;
}