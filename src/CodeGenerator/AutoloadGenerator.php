<?php

declare (strict_types=1);
namespace Laminas\Di\Code_Generator;

use function array_keys;
use function array_map;
use function assert;
use function file_get_contents;
use function implode;
use function is_string;
use Laminas\Di\Exception\Generate_Code_Exception;
use Spl_File_Object;
use function sprintf;
use function str_repeat;
use function strtr;
use Throwable;
use function var_export;
/**
 * @deprecated Since 3.16.0, the code generator will be replaced by a separate package in version 4.0
 *
 * @final This class should not be extended and will be marked final in version 4.0
 */
class Autoload_Generator
{
    use Generator_Trait;
    private const CLASS_TEMPLATE = __DIR__ . '/../../templates/autoloader-class.template';
    private const FILE_TEMPLATE = __DIR__ . '/../../templates/autoloader-file.template';
    public function __construct(private string $namespace)
    {
    }
    private function write_file(string $filename, string $code): void
    {
        try {
            $file = new Spl_File_Object($filename, 'w');
            $file->fwrite($code);
        } catch (Throwable $e) {
            throw new Generate_Code_Exception(sprintf('Failed to write output file "%s"', $filename), 0, $e);
        }
    }
    private function build_from_template(string $template_file, string $output_file, array $replacements): void
    {
        $template = file_get_contents($template_file);
        assert(is_string($template));
        assert(is_string($this->output_directory));
        $this->write_file(sprintf('%s/%s', $this->output_directory, $output_file), strtr($template, $replacements));
    }
    /**
     * @param array<string, string> $classmap
     */
    private function generate_classmap_code(array &$classmap): string
    {
        $lines = array_map(static fn(string $class, string $file): string => sprintf('%s => %s,', var_export($class, true), var_export($file, true)), array_keys($classmap), $classmap);
        $indentation = sprintf("\n%s", str_repeat(' ', 8));
        return implode($indentation, $lines);
    }
    /**
     * @param array<string, string> $classmap
     */
    private function generate_autoloader_class(array &$classmap): void
    {
        $this->build_from_template(self::CLASS_TEMPLATE, 'Autoloader.php', ['%namespace%' => $this->namespace ? sprintf("namespace %s;\n", $this->namespace) : '', '%classmap%' => $this->generate_classmap_code($classmap)]);
    }
    private function generate_autoload_file(): void
    {
        $this->build_from_template(self::FILE_TEMPLATE, 'autoload.php', ['%namespace%' => $this->namespace ? sprintf("namespace %s;\n", $this->namespace) : '']);
    }
    /**
     * @param array<string, string> $classmap
     */
    public function generate(array &$classmap): void
    {
        $this->ensure_output_directory();
        $this->generate_autoloader_class($classmap);
        $this->generate_autoload_file();
    }
}