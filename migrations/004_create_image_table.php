<?php
class Migration004createImageTable
{
    public function up(PDO $pdo)
    {
        $tableName = 'image';

        $stmt        = $pdo->query("SHOW TABLES LIKE '$tableName'");
        $tableExists = $stmt->rowCount() > 0;

        // If the table doesn't exist, create it
        if (!$tableExists) {
            $createTableQuery = " CREATE TABLE $tableName (
                id INT PRIMARY KEY AUTO_INCREMENT,
                filename VARCHAR(255) NOT NULL,
                original_filename VARCHAR(255) NOT NULL,
                table_name VARCHAR(255) NOT NULL,
                table_id INT NOT NULL,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                created_by VARCHAR(255) NOT NULL,
                updated_at timestamp DEFAULT CURRENT_TIMESTAMP,
                updated_by VARCHAR(255) NOT NULL
            )";
            $pdo->exec($createTableQuery);
        }
    }

    public function down(PDO $pdo)
    {
        $tableName = 'image';
        $pdo->exec("DROP TABLE IF EXISTS `$tableName`;");
        echo "↩️ Dropped table '$tableName'\n";
    }
}