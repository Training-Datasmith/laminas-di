<?php

declare (strict_types=1);
namespace Laminas\Di;

/**
 * Provides the instance and resolver configuration
 */
interface Config_Interface
{
    /**
     * Check if the provided type name is aliased
     */
    public function is_alias(string $name): bool;
    /**
     * @return string[]
     */
    public function get_configured_type_names(): array;
    /**
     * Returns the actual class name for an alias
     *
     * @return class-string|null
     */
    public function get_class_for_alias(string $name): ?string;
    /**
     * Returns the instantiation parameters for the given type
     *
     * @param  string $type The alias or class name
     * @return array<array-key, mixed> The configured parameters
     */
    public function get_parameters(string $type): array;
    /**
     * Set the instantiation parameters for the given type
     *
     * @param array<array-key, mixed> $params
     * @return mixed
     */
    public function set_parameters(string $type, array $params);
    /**
     * Configured type preference
     */
    public function get_type_preference(string $type, ?string $context_class = null): ?string;
}