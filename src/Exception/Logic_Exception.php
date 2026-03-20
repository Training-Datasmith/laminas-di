<?php

declare (strict_types=1);
namespace Laminas\Di\Exception;

use LogicException as BaseLogicException;
class LogicException extends Base_Logic_Exception implements Exception_Interface
{
}