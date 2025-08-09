<?php
// migrate.php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load .env variables from project root
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
// Set up your DB connection (replace with your actual settings)
$host     = $_ENV['DB_HOST'];
$dbname   = $_ENV['DB_NAME'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create migrations table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $command = $argv[1] ?? 'up';
    $files   = glob(__DIR__ . '/migrations/*.php');
    sort($files);

    $executed = $pdo->query("SELECT filename FROM migrations ORDER BY id DESC")->fetchAll(PDO::FETCH_COLUMN);

    if ($command === 'up') {
        foreach ($files as $file) {
            $filename = basename($file);
            if (!in_array($filename, $executed)) {
                include $file;
                // Convert filename to class name:
                $className = 'Migration' . preg_replace('/[^0-9a-zA-Z]/', '', pathinfo($filename, PATHINFO_FILENAME));
                if (class_exists($className)) {
                    $migration = new $className();
                    $migration->up($pdo);
                    $stmt = $pdo->prepare("INSERT INTO migrations (filename) VALUES (:filename)");
                    $stmt->execute(['filename' => $filename]);
                    echo "✔ Completed: $filename\n";
                } else {
                    echo "❌ Migration class $className not found in $filename\n";
                    exit(1);
                }
            } else {
                echo "⏩ Skipping (already executed): $filename\n";
            }
        }
    } elseif ($command === 'down') {
        if (empty($executed)) {
            echo "ℹ️ No migrations to rollback.\n";
            exit(0);
        }
        $lastMigration = $executed[0];
        $file          = __DIR__ . '/migrations/' . $lastMigration;
        if (file_exists($file)) {
            include $file;
            $className = 'Migration' . preg_replace('/[^0-9a-zA-Z]/', '', pathinfo($lastMigration, PATHINFO_FILENAME));
            if (class_exists($className)) {
                $migration = new $className();
                $migration->down($pdo);
                $stmt = $pdo->prepare("DELETE FROM migrations WHERE filename = :filename");
                $stmt->execute(['filename' => $lastMigration]);
                echo "✔ Rolled back: $lastMigration\n";
            } else {
                echo "❌ Migration class $className not found in $lastMigration\n";
            }
        } else {
            echo "❌ Migration file not found: $lastMigration\n";
        }
    } else {
        echo "❌ Unknown command. Use 'up' or 'down'.\n";
    }
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}
