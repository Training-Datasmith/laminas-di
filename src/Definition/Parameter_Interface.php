<?php

declare (strict_types=1);
namespace Laminas\Di\Definition;

/**
 * Parameter definition
 */
interface Parameter_Interface
{
    public function get_name(): string;
    public function get_position(): int;
    public function get_type(): ?string;
    /**
     * @return mixed
     */
    public function get_default();
    public function is_required(): bool;
    public function is_builtin(): bool;
}