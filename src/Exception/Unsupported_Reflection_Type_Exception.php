<?php

declare (strict_types=1);
namespace Laminas\Di\Exception;

use ReflectionNamedType;
use Reflection_Type;
use function sprintf;
/**
 * This Exception class is intended to be thrown whenever a
 * ReflectionUnionType::class or ReflectionIntersectionType::class
 * is returned to code that can only function correctly with an instance
 * of ReflectionType::class pre PHP 8.0.
 */
final class Unsupported_Reflection_Type_Exception extends RuntimeException
{
    private function __construct(Reflection_Type $reflection_type)
    {
        parent::__construct(sprintf("Unusable reflection type '%s', object of type '%s' required", $reflection_type::class, ReflectionNamedType::class));
    }
    public static function from_union_or_intersection_type(Reflection_Type $reflection_type): self
    {
        return new self($reflection_type);
    }
}