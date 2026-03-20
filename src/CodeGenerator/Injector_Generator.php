<?php

declare (strict_types=1);
namespace Laminas\Di\Code_Generator;

use function array_keys;
use function array_map;
use function assert;
use function file_get_contents;
use function implode;
use function is_string;
use Laminas\Di\Config_Interface;
use Laminas\Di\Definition\Definition_Interface;
use Laminas\Di\Resolver\Dependency_Resolver_Interface;
use Psr\Log\Logger_Interface;
use Psr\Log\Null_Logger;
use Spl_File_Object;
use function sprintf;
use function str_repeat;
use function strtr;
use Throwable;
use function var_export;
/**
 * Generator for the dependency injector
 *
 * Generates an Injector class that will use a generated factory for a requested
 * type, if available. This factory will contain pre-resolved dependencies
 * from the provided configuration, definition and resolver instances.
 *
 * @deprecated Since 3.16.0, the code generator will be replaced by a separate package in version 4.0
 *
 * @final This class should not be extended and will be marked final in version 4.0
 */
class Injector_Generator
{
    use Generator_Trait;
    private const FACTORY_LIST_TEMPLATE = __DIR__ . '/../../templates/factory-list.template';
    private const INJECTOR_TEMPLATE = __DIR__ . '/../../templates/injector.template';
    private const INDENTATION_SPACES = 4;
    /**
     * @deprecated
     *
     * @var DefinitionInterface|null
     */
    protected $definition;
    private string $namespace;
    private Factory_Generator $factory_generator;
    private Autoload_Generator $autoload_generator;
    /**
     * Constructs the compiler instance
     *
     * @param ConfigInterface             $config The configuration to compile from
     * @param DependencyResolverInterface $resolver The resolver to utilize
     * @param string|null                 $namespace Namespace to use for generated class; defaults
     *                     to Laminas\Di\Generated.
     * @param LoggerInterface|null        $logger An optional logger instance to log failures
     *            and processed classes.
     */
    public function __construct(private Config_Interface $config, Dependency_Resolver_Interface $resolver, ?string $namespace = null, private ?Logger_Interface $logger = new Null_Logger())
    {
        $this->namespace = $namespace ?: 'Laminas\Di\Generated';
        $this->factory_generator = new Factory_Generator($config, $resolver, $this->namespace . '\Factory');
        $this->autoload_generator = new Autoload_Generator($this->namespace);
    }
    private function build_from_template(string $template_file, string $output_file, array $replacements): void
    {
        $template = file_get_contents($template_file);
        assert(is_string($template));
        $code = strtr($template, $replacements);
        $file = new Spl_File_Object($output_file, 'w');
        $file->fwrite($code);
        $file->fflush();
    }
    private function generate_injector(): void
    {
        assert(is_string($this->output_directory));
        $this->build_from_template(self::INJECTOR_TEMPLATE, sprintf('%s/GeneratedInjector.php', $this->output_directory), ['%namespace%' => $this->namespace ? "namespace {$this->namespace};\n" : '']);
    }
    /**
     * @param array<string, string> $factories
     */
    private function generate_factory_list(array $factories): void
    {
        $indentation = sprintf("\n%s", str_repeat(' ', self::INDENTATION_SPACES));
        $code_lines = array_map(static fn(string $key, string $value): string => sprintf('%s => %s,', var_export($key, true), var_export($value, true)), array_keys($factories), $factories);
        assert(is_string($this->output_directory));
        $this->build_from_template(self::FACTORY_LIST_TEMPLATE, sprintf('%s/factories.php', $this->output_directory), ['%factories%' => implode($indentation, $code_lines)]);
    }
    /**
     * @param array<string, string> $factories
     */
    private function generate_type_factory(string $class, array &$factories): void
    {
        if (isset($factories[$class])) {
            return;
        }
        $this->logger->debug(sprintf('Generating factory for class "%s"', $class));
        try {
            $factory = $this->factory_generator->generate($class);
            if ($factory) {
                $factories[$class] = $factory;
            }
        } catch (Throwable $e) {
            $this->logger->error(sprintf('Could not create factory for "%s": %s', $class, $e->get_message()));
        }
    }
    private function generate_autoload(): void
    {
        $add_factory_prefix = static fn(string $value): string => 'Factory/' . $value;
        $classmap = array_map($add_factory_prefix, $this->factory_generator->get_classmap());
        $classmap[$this->namespace . '\GeneratedInjector'] = 'GeneratedInjector.php';
        $this->autoload_generator->generate($classmap);
    }
    /**
     * Returns the namespace this generator uses
     */
    public function get_namespace(): string
    {
        return $this->namespace;
    }
    /**
     * Generate the injector
     *
     * This will generate the injector and its factories into the output directory
     *
     * @param class-string[] $classes
     */
    public function generate($classes = []): void
    {
        $this->ensure_output_directory();
        $this->factory_generator->set_output_directory($this->output_directory . '/Factory');
        $this->autoload_generator->set_output_directory($this->output_directory);
        $factories = [];
        foreach ($classes as $class) {
            $this->generate_type_factory($class, $factories);
        }
        foreach ($this->config->get_configured_type_names() as $type) {
            $this->generate_type_factory($type, $factories);
        }
        $this->generate_autoload();
        $this->generate_injector();
        $this->generate_factory_list($factories);
    }
}