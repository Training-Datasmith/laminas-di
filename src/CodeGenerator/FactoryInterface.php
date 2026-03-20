<?php

declare (strict_types=1);
namespace Laminas\Di\Code_Generator;

use Psr\Container\Container_Interface;
/**
 * @deprecated Since 3.16.0, the code generator will be replaced by a separate package in version 4.0
 *
 * @template T extends object
 */
interface Factory_Interface
{
    /**
     * Create an instance
     *
     * @param array<mixed> $options
     * @return T
     */
    public function create(Container_Interface $container, array $options);
}