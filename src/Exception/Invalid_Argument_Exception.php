<?php

declare (strict_types=1);
namespace Laminas\Di\Exception;

use InvalidArgumentException as BaseInvalidArgumentException;
class InvalidArgumentException extends Base_Invalid_Argument_Exception implements Exception_Interface
{
}