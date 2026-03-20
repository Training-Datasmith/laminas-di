<?php

declare (strict_types=1);
namespace Laminas\Di;

use function array_pop;
use ArrayAccess;
use function assert;
use function class_exists;
use const E_USER_DEPRECATED;
use function is_array;
use function is_iterable;
use function is_string;
use Laminas\Stdlib\Array_Utils;
use Laminas\Stdlib\Parameters;
use function str_contains;
use Traversable;
use function trigger_error;
/**
 * Provides a migration config from laminas-di 2.x configuration arrays
 *
 * @deprecated Since 3.16.0. This class will be removed in version 4.0. Use {@link Config}
 *             or {@link ConfigInterface} instead.
 *
 * @final
 */
class Legacy_Config extends Config
{
    /**
     * @param iterable<mixed>|ArrayAccess<mixed, mixed> $config
     */
    public function __construct($config)
    {
        parent::__construct();
        if ($config instanceof Traversable) {
            $config = Array_Utils::iterator_to_array($config);
        }
        /** @psalm-suppress DocblockTypeContradiction Can this whole typecheck statement be dropped? */
        if (!is_array($config) && !$config instanceof ArrayAccess) {
            throw new Exception\InvalidArgumentException('Config data must be an array or implement Traversable');
        }
        if (isset($config['instance']) && is_iterable($config['instance'])) {
            $this->configure_instance($config['instance']);
        }
    }
    /**
     * @psalm-suppress MixedAssignment
     * @param iterable<mixed> $parameters
     * @return array<array-key, mixed>
     */
    private function prepare_parameters_array(iterable $parameters): array
    {
        $prepared = [];
        foreach ($parameters as $key => $value) {
            $key = (string) $key;
            if (str_contains($key, ':')) {
                trigger_error('Full qualified parameter positions are no longer supported', E_USER_DEPRECATED);
            }
            $prepared[$key] = $value;
        }
        return $prepared;
    }
    /**
     * @psalm-suppress MixedAssignment
     * @param iterable<mixed> $config
     */
    private function configure_instance(iterable $config): void
    {
        /** @var mixed $data*/
        foreach ($config as $target => $data) {
            switch ($target) {
                case 'aliases':
                case 'alias':
                    assert(is_iterable($data));
                    foreach ($data as $name => $class) {
                        if (is_string($class) && class_exists($class)) {
                            $this->set_alias((string) $name, $class);
                        }
                    }
                    break;
                case 'preferences':
                case 'preference':
                    assert(is_iterable($data));
                    foreach ($data as $type => $pref) {
                        $preference = is_array($pref) ? array_pop($pref) : $pref;
                        $this->set_type_preference((string) $type, (string) $preference);
                    }
                    break;
                default:
                    assert(is_string($target));
                    $config = new Parameters(is_array($data) ? $data : []);
                    $parameters = $config->get('parameters', $config->get('parameter'));
                    if (is_iterable($parameters)) {
                        $parameters = $this->prepare_parameters_array($parameters);
                        $this->set_parameters($target, $parameters);
                    }
                    break;
            }
        }
    }
    /**
     * Export the configuration to an array
     */
    public function to_array(): array
    {
        return ['preferences' => $this->preferences, 'types' => $this->types];
    }
}