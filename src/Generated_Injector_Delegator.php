<?php

declare (strict_types=1);
namespace Laminas\Di;

use function class_exists;
use function is_string;
use Laminas\Di\Exception\Invalid_Service_Config_Exception;
use Psr\Container\Container_Interface;
/**
 * @final
 */
class Generated_Injector_Delegator
{
    /**
     * @psalm-suppress MixedAssignment Laminas config is an untyped array - types should be ensured internally
     * @psalm-suppress MixedArrayAccess Laminas config is an untyped array - types should be ensured internally
     * @param string $name
     * @param callable():InjectorInterface $callback
     */
    public function __invoke(Container_Interface $container, $name, callable $callback): Injector_Interface
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $aot_config = $config['dependencies']['auto']['aot'] ?? [];
        $namespace = !isset($aot_config['namespace']) || $aot_config['namespace'] === '' ? 'Laminas\Di\Generated' : $aot_config['namespace'];
        if (!is_string($namespace)) {
            throw new Invalid_Service_Config_Exception('Provided namespace is not a string.');
        }
        $injector = $callback();
        $generated_injector = $namespace . '\GeneratedInjector';
        if (class_exists($generated_injector)) {
            /** @psalm-var class-string<InjectorInterface> $generatedInjector */
            return new $generated_injector($injector);
        }
        return $injector;
    }
}