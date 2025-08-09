<?php
class Migration002CreateUsersTable
{
    public function up(PDO $pdo)
    {
        $tableName = 'users';

        $stmt        = $pdo->query("SHOW TABLES LIKE '$tableName'");
        $tableExists = $stmt->rowCount() > 0;

        // If the table doesn't exist, create it
        if (!$tableExists) {
            $createTableQuery = "
            CREATE TABLE " . $tableName . " (
                id INT PRIMARY KEY AUTO_INCREMENT,
                fname VARCHAR(255) NOT NULL,
                lname VARCHAR(255) NOT NULL,
                passport VARCHAR(13) UNIQUE NOT NULL,
                address text NOT NULL,
                phonenumber VARCHAR(10) NOT NULL,
                birthdate date NOT NULL,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp DEFAULT CURRENT_TIMESTAMP
            )";
            $pdo->exec($createTableQuery);
        }
    }

    public function down(PDO $pdo)
    {
        $tableName = 'users';
        $pdo->exec("DROP TABLE IF EXISTS `$tableName`;");
        echo "↩️ Dropped table '$tableName'\n";
    }
}