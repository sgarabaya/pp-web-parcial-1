<?php
abstract class Config
{
    private static function get_or(string $name, string $default = ""): string
    {
        $var = getenv($name, true);
        return $var && !empty($var) ? $var : $default;
    }

    public static function getDbHost(): string
    {
        return self::get_or("DB_HOST");
    }
    public static function getDbName(): string
    {
        return self::get_or("DB_NAME");
    }

    public static function getDbUser(): string
    {
        return self::get_or("DB_USER");
    }

    public static function getDbPwd(): string
    {
        return self::get_or("DB_PWD");
    }
}
?>
