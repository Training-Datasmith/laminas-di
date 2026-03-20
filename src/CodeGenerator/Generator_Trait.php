<?php

declare (strict_types=1);
namespace Laminas\Di\Code_Generator;

use function assert;
use function is_dir;
use function is_string;
use Laminas\Di\Exception\Generate_Code_Exception;
use Laminas\Di\Exception\LogicException;
use function mkdir;
use function sprintf;
/**
 * Trait with generic generator utility methods
 *
 * @deprecated Since 3.16.0, the code generator will be replaced by a separate package in version 4.0
 */
trait Generator_Trait
{
    /** @var int */
    protected $mode = 0755;
    /** @var string|null */
    protected $output_directory;
    /**
     * Ensure that the given directory exists
     *
     * This will check the path at $dir if it exsits and if it is a directory
     *
     * @throws GenerateCodeException
     * @return void
     */
    protected function ensure_directory(string $dir)
    {
        assert(is_string($this->output_directory));
        if (!is_dir($dir) && !mkdir($dir, $this->mode, true)) {
            throw new Generate_Code_Exception(sprintf('Could not create output directory: %s', $dir));
        }
    }
    /**
     * Ensures the existence of the output directory
     *
     * @throws LogicException
     * @throws GenerateCodeException
     * @return void
     * @psalm-assert non-empty-string $this->outputDirectory
     */
    protected function ensure_output_directory()
    {
        if (!$this->output_directory) {
            throw new LogicException('Cannot generate code without output directory');
        }
        $this->ensure_directory($this->output_directory);
    }
    /**
     * Set the output directory
     *
     * You should configure a psr-4 autoloader with the namespace `Laminas\Di\Generated`
     * to src/ in this directory.
     *
     * The compiler will attempt to create this directory if it does not exist
     *
     * @param string   $dir The path to the output directory
     * @param null|int $mode The creation mode for the directory
     * @return $this Provides a fluent interface
     */
    public function set_output_directory(string $dir, ?int $mode = null): self
    {
        $this->output_directory = $dir;
        if ($mode !== null) {
            $this->mode = $mode;
        }
        return $this;
    }
    public function get_output_directory(): ?string
    {
        return $this->output_directory;
    }
}