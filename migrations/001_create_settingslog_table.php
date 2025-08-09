<?php
class Migration001CreateSettingsLogTable
{
    public function up(PDO $pdo)
    {
        $tableName   = "settingsLog";
        $stmt        = $pdo->query("SHOW TABLES LIKE '$tableName'");
        $tableExists = $stmt->rowCount() > 0;

        if (!$tableExists) {
            $createTableQuery = "
            CREATE TABLE $tableName(
                id INT PRIMARY KEY AUTO_INCREMENT,
                key_name VARCHAR(255) NOT NULL,
                value VARCHAR(255) NOT NULL,
                create_at timestamp DEFAULT CURRENT_TIMESTAMP,
                user VARCHAR(255) NULL
            )";
            $pdo->exec($createTableQuery);


            $sql  = "INSERT INTO `$tableName` (`key_name`,`value`) VALUES ('register','1');
                INSERT INTO `$tableName` (`key_name`,`value`) VALUES ('register_with_google','0');
                INSERT INTO `$tableName` (`key_name`,`value`) VALUES ('login_with_google','0');
                INSERT INTO `$tableName` (`key_name`,`value`) VALUES ('client_id','');
                INSERT INTO `$tableName` (`key_name`,`value`) VALUES ('client_secret','');";
            $stmt = $pdo->prepare($sql);

            $stmt->execute();
        }

    }

    public function down(PDO $pdo)
    {
        $tableName = 'settingsLog';
        $pdo->exec("DROP TABLE IF EXISTS `$tableName`;");
        echo "↩️ Dropped table '$tableName'\n";
    }
}