<?php

declare (strict_types=1);
namespace Laminas\Di\Container;

use function array_merge_recursive;
use ArrayAccess;
use function assert;
use const E_USER_DEPRECATED;
use function is_array;
use function is_iterable;
use Laminas\Di\Config;
use Laminas\Di\Config_Interface;
use Laminas\Di\Legacy_Config;
use Psr\Container\Container_Interface;
use function trigger_error;
/**
 * Factory implementation for creating the definition list
 *
 * @final
 */
class Config_Factory
{
    /**
     * @psalm-suppress MixedArrayAccess
     * @return Config
     */
    public function create(Container_Interface $container): Config_Interface
    {
        $config = $container->has('config') ? $container->get('config') : [];
        /** @var mixed $data */
        $data = $config['dependencies']['auto'] ?? [];
        $legacy_data = $config['di'] ?? null;
        assert(is_array($data));
        if ($legacy_data !== null) {
            trigger_error('Detected legacy DI configuration, please upgrade to v3. ' . 'See https://docs.laminas.dev/laminas-di/migration/ for details.', E_USER_DEPRECATED);
            assert(is_iterable($legacy_data) || $legacy_data instanceof ArrayAccess);
            $legacy_config = new Legacy_Config($legacy_data);
            $data = array_merge_recursive($legacy_config->to_array(), $data);
        }
        return new Config($data);
    }
    /**
     * Make the instance invokable
     */
    public function __invoke(Container_Interface $container): Config_Interface
    {
        return $this->create($container);
    }
}