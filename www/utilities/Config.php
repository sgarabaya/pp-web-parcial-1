<?php
abstract class Config
{
    public static function getDbHost(): string
    {
        return "db";
    }
    public static function getDbName(): string
    {
        return "ruta9";
    }

    public static function getDbUser(): string
    {
        return "root";
    }

    public static function getDbPwd(): string
    {
        return "root";
    }
}
?>
