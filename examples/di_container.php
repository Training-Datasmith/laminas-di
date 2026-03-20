<?php

declare(strict_types=1);

/**
 * Example: automatic dependency injection with laminas-di.
 *
 * Run from the laminas-di project root:
 *   php examples/di_container.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Laminas\Di\InjectorInterface;
use Laminas\Di\Injector;
use Laminas\Di\Config;

// --- Define simple classes with constructor injection ---

class Logger
{
    public function log(string $message): void
    {
        echo "[LOG] $message\n";
    }
}

class Database
{
    public function __construct(private readonly Logger $logger)
    {
        $this->logger->log('Database connected.');
    }

    public function query(string $sql): string
    {
        $this->logger->log("Query: $sql");
        return "result-set";
    }
}

class UserService
{
    public function __construct(
        private readonly Database $db,
        private readonly Logger   $logger,
    ) {}

    public function get_user(int $id): array
    {
        $this->logger->log("Fetching user $id");
        $result = $this->db->query("SELECT * FROM users WHERE id = $id");
        return ['id' => $id, 'result' => $result];
    }
}

// --- Wire via laminas-di ---
$config   = new Config([]);
$injector = new Injector($config);

/** @var UserService $userService */
$userService = $injector->create(UserService::class);
$user        = $userService->get_user(42);

echo "User id: "     . $user['id']     . "\n";
echo "DB result: "   . $user['result'] . "\n\n";

// --- Show the injector can resolve nested deps automatically ---
echo "Injector type: " . get_class($injector) . "\n";
echo "Implements InjectorInterface: " . ($injector instanceof InjectorInterface ? 'yes' : 'no') . "\n";
