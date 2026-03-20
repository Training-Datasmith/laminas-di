<?php

declare (strict_types=1);
namespace Laminas\Di\Code_Generator;

use function assert;
use function dirname;
use function file_get_contents;
use function implode;
use function is_string;
use Laminas\Di\Config_Interface;
use Laminas\Di\Exception\RuntimeException;
use Laminas\Di\Resolver\Dependency_Resolver_Interface;
use Laminas\Di\Resolver\Injection_Interface;
use Laminas\Di\Resolver\Type_Injection;
use function preg_replace;
use Spl_File_Object;
use function sprintf;
use function str_repeat;
use function str_replace;
use function strrpos;
use function strtr;
use function substr;
use function var_export;
/**
 * Generates factory classes
 *
 * @deprecated Since 3.16.0, the code generator will be replaced by a separate package in version 4.0
 *
 * @final This class should not be extended and will be marked final in version 4.0
 */
class Factory_Generator
{
    use Generator_Trait;
    private const INDENTATION_SPACES = 4;
    private const TEMPLATE_FILE = __DIR__ . '/../../templates/factory.template';
    private const PARAMETERS_TEMPLATE = <<<'__CODE__'
    
            $args = empty($options)
                ? [
                    %s
                ]
                : [
                    %s
                ];
    
    __CODE__;
    private string $namespace;
    /** @var array<string, string> */
    private array $classmap = [];
    public function __construct(private Config_Interface $config, private Dependency_Resolver_Interface $resolver, ?string $namespace = null)
    {
        $this->namespace = $namespace ?: 'LaminasDiGenerated';
    }
    protected function build_class_name(string $name): string
    {
        return preg_replace('~[^a-z0-9\\\\]+~i', '_', $name) . 'Factory';
    }
    protected function build_file_name(string $name): string
    {
        return str_replace('\\', '/', $this->build_class_name($name)) . '.php';
    }
    /**
     * @return string[] The resulting parts as [$namspace, $unqualifiedClassName]
     */
    private function split_fully_qualified_class_name(string $class): array
    {
        $pos = strrpos($class, '\\');
        if ($pos === false) {
            return ['', $class];
        }
        $namespace = substr($class, 0, $pos);
        $unqualified_class_name = substr($class, $pos + 1);
        return [$namespace, $unqualified_class_name];
    }
    private function get_class_name(string $type): string
    {
        if ($this->config->is_alias($type)) {
            return $this->config->get_class_for_alias($type) ?? $type;
        }
        return $type;
    }
    /**
     * @param InjectionInterface[] $injections
     */
    private function can_generate_for_parameters(iterable $injections): bool
    {
        foreach ($injections as $injection) {
            if (!$injection->is_exportable()) {
                return false;
            }
        }
        return true;
    }
    /**
     * Builds the code for constructor parameters
     *
     * @param InjectionInterface[] $injections
     */
    private function build_parameters_code(iterable $injections): ?string
    {
        $with_options = [];
        $without_options = [];
        foreach ($injections as $name => $injection) {
            $code = $injection->export();
            if ($injection instanceof Type_Injection) {
                $code = '$container->get(' . $code . ')';
            }
            // build for two cases:
            // 1. Parameters are passed at call time
            // 2. No Parameters were passed at call time (might be slightly faster)
            $without_options[] = sprintf('%s, // %s', $code, $name);
            $with_options[] = sprintf('array_key_exists(%1$s, $options) ? $options[%1$s] : %2$s,', var_export($name, true), $code);
        }
        if (!$with_options) {
            return null;
        }
        $tabs = sprintf("\n%s", str_repeat(' ', self::INDENTATION_SPACES * 4));
        // Build conditional initializer code:
        // If no $params were provided ignore it completely
        // otherwise check if there is a value for each dependency in $params.
        return sprintf(self::PARAMETERS_TEMPLATE, implode($tabs, $without_options), implode($tabs, $with_options));
    }
    /**
     * @throws RuntimeException When generating the factory failed.
     */
    public function generate(string $class): string
    {
        $class_name = $this->get_class_name($class);
        $injections = $this->resolver->resolve_parameters($class_name);
        if (!$this->can_generate_for_parameters($injections)) {
            throw new RuntimeException(sprintf('Cannot generate parameter code for type "%s" (class: "%s")', $class, $class_name));
        }
        $params_code = $this->build_parameters_code($injections);
        $absolute_class_name = '\\' . $class_name;
        $factory_class_name = $this->namespace . '\\' . $this->build_class_name($class);
        [$namespace, $unqualified_factory_class_name] = $this->split_fully_qualified_class_name($factory_class_name);
        assert(is_string($this->output_directory));
        $filename = $this->build_file_name($class);
        $filepath = $this->output_directory . '/' . $filename;
        $template = file_get_contents(self::TEMPLATE_FILE);
        assert(is_string($template));
        $code = strtr($template, ['%class%' => $absolute_class_name, '%namespace%' => $namespace ? "namespace {$namespace};\n" : '', '%factory_class%' => $unqualified_factory_class_name, '%options_to_args_code%' => $params_code, '%use_array_key_exists%' => $params_code ? "\nuse function array_key_exists;" : '', '%args%' => $params_code ? '...$args' : '', '%psalm_suppress%' => $params_code ? "\n        /** @psalm-suppress MixedArgument */" : '']);
        $this->ensure_directory(dirname($filepath));
        $output = new Spl_File_Object($filepath, 'w');
        $output->fwrite($code);
        $this->classmap[$factory_class_name] = $filename;
        return $factory_class_name;
    }
    /**
     * @return array<string, string>
     */
    public function get_classmap(): array
    {
        return $this->classmap;
    }
}