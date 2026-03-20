<?php

declare (strict_types=1);
namespace Laminas\Di\Container\Service_Manager;

use Laminas\Di\Container\Autowire_Factory as GenericAutowireFactory;
use Laminas\Service_Manager\Factory\Abstract_Factory_Interface;
use Psr\Container\Container_Interface;
/**
 * Create instances with autowiring
 *
 * This class is purely for compatibility with Laminas\ServiceManager interface which requires container-interop
 *
 * @final
 */
class Autowire_Factory implements Abstract_Factory_Interface
{
    private readonly Generic_Autowire_Factory $factory;
    public function __construct(?Generic_Autowire_Factory $factory = null)
    {
        $this->factory = $factory ?: new Generic_Autowire_Factory();
    }
    /**
     * Check creatability of the requested name
     *
     * @param string $requestedName
     * @return bool
     */
    public function can_create(Container_Interface $container, $requested_name)
    {
        return $this->factory->can_create($container, $requested_name);
    }
    /**
     * Make invokable and implement the laminas-service factory pattern
     *
     * @psalm-suppress RedundantCastGivenDocblockType
     * @param string $requestedName
     * @return object
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null)
    {
        return $this->factory->create($container, (string) $requested_name, $options);
    }
}