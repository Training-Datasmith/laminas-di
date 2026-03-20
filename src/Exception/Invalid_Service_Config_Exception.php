<?php

declare (strict_types=1);
namespace Laminas\Di\Exception;

use Psr\Container\Container_Exception_Interface;
/**
 * @final This class should not be extended and will be marked final in version 4.0
 */
class Invalid_Service_Config_Exception extends LogicException implements Container_Exception_Interface
{
}