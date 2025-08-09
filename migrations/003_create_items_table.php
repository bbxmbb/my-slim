<?php
class Migration003CreateItemsTable
{
    public function up(PDO $pdo)
    {
        $tableName = 'items';

        $stmt        = $pdo->query("SHOW TABLES LIKE '$tableName'");
        $tableExists = $stmt->rowCount() > 0;

        // If the table doesn't exist, create it
        if (!$tableExists) {
            $createTableQuery = "
            CREATE TABLE $tableName (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                description VARCHAR(255) NOT NULL,
                numberValue int NOT NULL,
                booleanValue tinyint DEFAULT 0 ,
                arrayValue JSON NOT NULL,
                objectValue JSON NOT NULL,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp DEFAULT CURRENT_TIMESTAMP
            )";
            $pdo->exec($createTableQuery);
        }
    }

    public function down(PDO $pdo)
    {
        $tableName = 'items';
        $pdo->exec("DROP TABLE IF EXISTS `$tableName`;");
        echo "↩️ Dropped table '$tableName'\n";
    }
}