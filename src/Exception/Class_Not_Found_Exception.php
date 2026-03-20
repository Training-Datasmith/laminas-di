<?php

declare (strict_types=1);
namespace Laminas\Di\Exception;

use DomainException;
use Throwable;
/**
 * @final This class should not be extended and will be marked final in version 4.0
 */
class Class_Not_Found_Exception extends DomainException implements Exception_Interface
{
    public function __construct(string $classname, ?int $code = null, ?Throwable $previous = null)
    {
        parent::__construct("The class '{$classname}' does not exist.", $code ?? 0, $previous);
    }
}