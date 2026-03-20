<?php

declare (strict_types=1);
namespace Laminas\Di\Container;

use Laminas\Di\Config_Interface;
use Laminas\Di\Injector;
use Laminas\Di\Injector_Interface;
use Psr\Container\Container_Interface;
/**
 * Implements the DependencyInjector service factory for laminas-servicemanager
 *
 * @final
 */
class Injector_Factory
{
    private function create_config(Container_Interface $container): Config_Interface
    {
        if ($container->has(Config_Interface::class)) {
            return $container->get(Config_Interface::class);
        }
        if ($container->has('Zend\Di\ConfigInterface')) {
            /** @psalm-var ConfigInterface */
            return $container->get('Zend\Di\ConfigInterface');
        }
        return (new Config_Factory())->create($container);
    }
    /**
     * {@inheritDoc}
     */
    public function create(Container_Interface $container): Injector_Interface
    {
        $config = $this->create_config($container);
        return new Injector($config, $container);
    }
    /**
     * Make the instance invokable
     */
    public function __invoke(Container_Interface $container): Injector_Interface
    {
        return $this->create($container);
    }
}