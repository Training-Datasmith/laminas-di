# Architecture: laminas-di

## Purpose
Laminas Dependency Injector — a reflection-based automatic dependency injection container. Supports autowiring, explicit configuration, code generation for compiled containers, and integration with laminas-servicemanager.

## Directory Structure
```
src/
  Injector.php                        # Main DI container — create($class, $params)
  Injector_Interface.php              # Contract for DI containers
  Config.php / Config_Interface.php   # Runtime DI configuration (type preferences, aliases, etc.)
  Default_Container.php               # PSR-11-compatible container wrapper
  Resolver/
    Dependency_Resolver.php           # Resolves constructor parameters via reflection + config
    Dependency_Resolver_Interface.php
    Type_Injection.php                # Inject a service by type name
    Value_Injection.php               # Inject a literal value
  Definition/
    Reflection/Class_Definition.php   # Reflection-backed class parameter discovery
    Reflection/Parameter.php          # Wraps a ReflectionParameter
    Runtime_Definition.php            # Uses PHP reflection at runtime to discover dependencies
  CodeGenerator/
    Factory_Generator.php             # Generates PHP factory classes for compiled containers
    Injector_Generator.php            # Generates a compiled Injector class
    Autoload_Generator.php            # Generates autoload map for generated factories
    Abstract_Injector.php             # Base class for generated injectors
  Container/
    Autowire_Factory.php              # PSR-11 factory that autowires via Injector
    Injector_Factory.php              # Creates Injector instances from config
    ServiceManager/Autowire_Factory.php  # laminas-servicemanager integration
  Exception/                          # Typed exceptions (circular dependency, class not found, etc.)
  Module.php                          # laminas-mvc Module for DI integration
  Config_Provider.php                 # Mezzio/Laminas config provider
```

## Key Design Decisions
- **Reflection-first, config-override** — by default the injector uses PHP reflection to discover constructor parameters. Explicit configuration overrides take precedence for any class or parameter.
- **Code generation** — the `CodeGenerator` subsystem can pre-compile the full DI graph into PHP factory files, eliminating reflection overhead in production.
- **PSR-11 compatible** — `Default_Container` wraps the injector to implement `ContainerInterface`, enabling use with any PSR-11-aware framework.
- **Circular dependency detection** — `Dependency_Resolver` tracks in-progress resolutions and throws `Circular_Dependency_Exception` rather than infinite-looping.

## Extension Points
- Provide a custom `Config` to set type preferences, aliases, and parameter substitutions.
- Integrate with laminas-servicemanager via `Container\ServiceManager\Autowire_Factory`.
- Use code generation in deployment pipelines to eliminate runtime reflection cost.

## Dependency Flow
```
Injector::create(MyService::class)
  └─ Dependency_Resolver::resolve(MyService::class, $params)
       ├─ Runtime_Definition → ReflectionClass → constructor parameters
       ├─ Config → type preferences / parameter overrides
       └─ recurse: create(Dependency::class) for each unresolved param
            └─ instantiate MyService(new Dependency(...), ...)
```
