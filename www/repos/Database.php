<?php
class Database
{
    private static ?PDO $connection = null;

    public static function connect(): PDO
    {
        if (self::$connection === null) {
            $connectionString = sprintf(
                "mysql:host=%s;dbname=%s",
                Config::getDbHost(),
                Config::getDbName(),
            );

            self::$connection = new PDO(
                $connectionString,
                Config::getDbUser(),
                Config::getDbPwd(),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ],
            );
        }

        return self::$connection;
    }
}

?>
