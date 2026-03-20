<?php

declare (strict_types=1);
namespace Laminas\Di;

/**
 * Provides Module functionality for Laminas applications
 *
 * To add the DI integration to your application use laminas frameworks component installer or
 * add `Laminas\\Di` to the Laminas modules list:
 *
 * <code>
 *  // application.config.php
 *  return [
 *      // ...
 *      'modules' => [
 *          'Laminas\\Di',
 *          // ...
 *      ]
 *  ];
 * </code>
 *
 * @final
 * @psalm-import-type DependencyConfigArray from ConfigProvider
 */
class Module
{
    /**
     * Returns the configuration for laminas-mvc
     *
     * @return array{service_manager: DependencyConfigArray}
     */
    public function get_config(): array
    {
        $provider = new Config_Provider();
        return ['service_manager' => $provider->get_dependency_config()];
    }
}