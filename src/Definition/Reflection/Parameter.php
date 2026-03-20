<?php

declare (strict_types=1);
namespace Laminas\Di\Definition\Reflection;

use Laminas\Di\Definition\Parameter_Interface;
use Laminas\Di\Exception\Unsupported_Reflection_Type_Exception;
use ReflectionNamedType;
/**
 * This class specifies a method parameter for the di definition
 *
 * @final
 */
class Parameter implements Parameter_Interface
{
    public function __construct(protected \ReflectionParameter $reflection)
    {
    }
    /**
     * {@inheritDoc}
     *
     * @see ParameterInterface::getDefault()
     */
    public function get_default(): mixed
    {
        return $this->reflection->get_default_value();
    }
    /**
     * {@inheritDoc}
     *
     * @see ParameterInterface::getName()
     */
    public function get_name(): string
    {
        return $this->reflection->get_name();
    }
    /**
     * {@inheritDoc}
     *
     * @see ParameterInterface::getPosition()
     */
    public function get_position(): int
    {
        return $this->reflection->get_position();
    }
    /**
     * {@inheritDoc}
     *
     * @see ParameterInterface::getType()
     *
     * @throws UnsupportedReflectionTypeException
     */
    public function get_type(): ?string
    {
        $type = $this->reflection->get_type();
        if (!$type) {
            return null;
        }
        if (!$type instanceof ReflectionNamedType) {
            throw Unsupported_Reflection_Type_Exception::from_union_or_intersection_type($type);
        }
        return $type->get_name();
    }
    /**
     * {@inheritDoc}
     *
     * @see ParameterInterface::isRequired()
     */
    public function is_required(): bool
    {
        return !$this->reflection->is_optional();
    }
    /**
     * {@inheritDoc}
     *
     * @see ParameterInterface::isScalar()
     *
     * @throws UnsupportedReflectionTypeException
     */
    public function is_builtin(): bool
    {
        $type = $this->reflection->get_type();
        if (!$type) {
            return false;
        }
        if (!$type instanceof ReflectionNamedType) {
            throw Unsupported_Reflection_Type_Exception::from_union_or_intersection_type($type);
        }
        return $type->is_builtin();
    }
}