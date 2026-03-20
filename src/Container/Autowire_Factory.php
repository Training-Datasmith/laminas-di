<?php

declare (strict_types=1);
namespace Laminas\Di\Container;

use Laminas\Di\Exception;
use Laminas\Di\Injector_Interface;
use Psr\Container\Container_Interface;
/**
 * Create instances with autowiring
 *
 * @final
 */
class Autowire_Factory
{
    /**
     * Retrieves the injector from a container
     *
     * @param ContainerInterface $container The container context for this factory
     * @throws Exception\RuntimeException When no dependency injector is available.
     */
    private function get_injector(Container_Interface $container): Injector_Interface
    {
        $injector = $container->get(Injector_Interface::class);
        if (!$injector instanceof Injector_Interface) {
            throw new Exception\RuntimeException('Could not get a dependency injector form the container implementation');
        }
        return $injector;
    }
    /**
     * Check creatability of the requested name
     *
     * @param string $requestedName
     * @return bool
     */
    public function can_create(Container_Interface $container, $requested_name)
    {
        if (!$container->has(Injector_Interface::class)) {
            return false;
        }
        /** @psalm-suppress RedundantCastGivenDocblockType Avoid behavior BC break */
        return $this->get_injector($container)->can_create((string) $requested_name);
    }
    /**
     * Create an instance
     *
     * @template T of object
     * @param string|class-string<T> $requestedName
     * @param array<mixed>|null $options
     * @return T
     */
    public function create(Container_Interface $container, string $requested_name, ?array $options = null)
    {
        return $this->get_injector($container)->create($requested_name, $options ?: []);
    }
    /**
     * Make invokable and implement the laminas-service factory pattern
     *
     * @template T of object
     * @param string|class-string<T> $requestedName
     * @param array<mixed>|null $options
     * @return T
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null)
    {
        /** @psalm-suppress RedundantCastGivenDocblockType Avoid behavior BC break */
        return $this->create($container, (string) $requested_name, $options);
    }
}