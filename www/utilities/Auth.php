<?php

abstract class Auth
{
    private static $userId;
    private static $userRole;

    protected function __construct() {}

    public static function load(): void
    {
        session_start();
        self::$userId = Api::safe_get($_SESSION, "user.id");
        self::$userRole = Api::safe_get($_SESSION, "user.role");
    }

    private static function getRole(): ?string
    {
        return Api::safe_get($_SESSION, "user.role");
    }

    public static function requireRole(string $required_role): void
    {
        if (!self::$userId || !self::$userRole) {
            header("Location: /login.php");
            exit();
        }

        if ($required_role === "ANY") {
            return; //Si no hay un requerimiento de rol, volvemos (para el index)
        }
        if (self::$userRole === "ADMIN") {
            return; //El rol admin puede ver todo
        }
        if (self::$userRole !== $required_role) {
            header("Location: /index.php");
            exit();
        }
    }

    public static function hasRole(string $role): bool
    {
        if ($role === "ANY") {
            return true;
        }

        return self::$userRole === $role;
    }
}
