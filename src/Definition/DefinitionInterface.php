<?php

declare (strict_types=1);
namespace Laminas\Di\Definition;

use Laminas\Di\Exception\Class_Not_Found_Exception;
/**
 * Interface for class definitions
 */
interface Definition_Interface
{
    /**
     * All class names in this definition
     *
     * @return list<string>
     */
    public function get_classes(): array;
    /**
     * Whether a class exists in this definition
     */
    public function has_class(string $class): bool;
    /**
     * @param class-string $class
     * @throws ClassNotFoundException
     */
    public function get_class_definition(string $class): Class_Definition_Interface;
}