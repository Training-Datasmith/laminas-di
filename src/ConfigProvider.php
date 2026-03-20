<?php

declare (strict_types=1);
namespace Laminas\Di;

/**
 * Implements the config provider for mezzio
 *
 * @final
 * @psalm-type DependencyConfigArray = array{
 *  aliases: array<string, string>,
 *  factories: array<string, callable|class-string>,
 *  abstract_factories: list<callable|class-string>
 * }
 */
class Config_Provider
{
    /**
     * Implements the config provider
     *
     * @return array{dependencies: DependencyConfigArray} The configuration for mezzio
     */
    public function __invoke(): array
    {
        return ['dependencies' => $this->get_dependency_config()];
    }
    /**
     * Returns the dependency (service manager) configuration
     *
     * @return DependencyConfigArray
     */
    public function get_dependency_config(): array
    {
        return [
            // Legacy Zend Framework aliases
            'aliases' => ['Zend\Di\InjectorInterface' => Injector_Interface::class, 'Zend\Di\ConfigInterface' => Config_Interface::class, 'Zend\Di\CodeGenerator\InjectorGenerator' => Code_Generator\Injector_Generator::class],
            'factories' => [Injector_Interface::class => Container\Injector_Factory::class, Config_Interface::class => Container\Config_Factory::class, Code_Generator\Injector_Generator::class => Container\Generator_Factory::class],
            'abstract_factories' => [Container\Service_Manager\Autowire_Factory::class],
        ];
    }
}