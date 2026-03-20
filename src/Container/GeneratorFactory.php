<?php

declare (strict_types=1);
namespace Laminas\Di\Container;

use function assert;
use function is_string;
use Laminas\Di\Code_Generator\Injector_Generator;
use Laminas\Di\Config_Interface;
use Laminas\Di\Definition\Runtime_Definition;
use Laminas\Di\Resolver\Dependency_Resolver;
use Psr\Container\Container_Interface;
use Psr\Log\Logger_Interface;
/**
 * @deprecated Since 3.16.0, the code generator will be replaced by a separate package in version 4.0
 *
 * @final
 */
class Generator_Factory
{
    private function get_config(Container_Interface $container): Config_Interface
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
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArrayAccess
     */
    public function create(Container_Interface $container): Injector_Generator
    {
        $di_config = $this->get_config($container);
        $resolver = new Dependency_Resolver(new Runtime_Definition(), $di_config);
        $resolver->set_container($container);
        $config = $container->has('config') ? $container->get('config') : [];
        $aot_config = $config['dependencies']['auto']['aot'] ?? [];
        $namespace = $aot_config['namespace'] ?? null;
        $logger = null;
        if (isset($aot_config['logger'])) {
            $logger = $container->get((string) $aot_config['logger']);
            assert($logger instanceof Logger_Interface);
        }
        assert($namespace === null || is_string($namespace));
        $generator = new Injector_Generator($di_config, $resolver, $namespace, $logger);
        if (isset($aot_config['directory'])) {
            $generator->set_output_directory((string) $aot_config['directory']);
        }
        return $generator;
    }
    public function __invoke(Container_Interface $container): Injector_Generator
    {
        return $this->create($container);
    }
}