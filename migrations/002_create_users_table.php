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
                email VARCHAR(255) NOT NULL,
                google_sub_id VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                confirmation_code TEXT NOT NULL,
                confirmed TINYINT(1) DEFAULT 0 NOT NULL,
                reset_password_code TEXT NOT NULL,
                user_role SMALLINT DEFAULT 99 NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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