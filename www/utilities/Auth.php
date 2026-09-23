<?php

abstract class Auth
{
    private static $userId;
    private static $userRole;
    private static $userName;

    protected function __construct() {}

    public static function load(): void
    {
        session_start();
        self::$userId = Api::safe_get($_SESSION, "user.id");
        self::$userRole = Api::safe_get($_SESSION, "user.role");
        self::$userName = Api::safe_get($_SESSION, "user.name");
    }

    public static function getName(): string
    {
        return self::$userName;
    }

    private static function getRole(): ?string
    {
        return Api::safe_get($_SESSION, "user.role");
    }

    public static function login(): bool
    {
        $email = Api::safe_get($_POST, "email");
        $password = Api::safe_get($_POST, "password");

        if (!$email || !$password) {
            return false;
        }

        $userRepo = new UserRepository();
        $user = $userRepo->findByEmail($email);

        if ($user && Crypto::passwordVerify($password, $user->passwordHash)) {
            $_SESSION["user.id"] = $user->id;
            $_SESSION["user.role"] = $user->role;
            $_SESSION["user.name"] = sprintf(
                "%s.%s",
                substr($user->name, 0, 1),
                $user->lastName,
            );

            return true;
        }

        return false;
    }

    public static function ensureLoggedIn(): void
    {
        if (!self::$userId || !self::$userRole) {
            Api::redirect("/login.php");
        }
    }

    public static function requireRole(string $required_role): void
    {
        if (!self::$userId || !self::$userRole) {
            Api::redirect("/login.php");
        }

        if ($required_role === "ANY") {
            return; //Si no hay un requerimiento de rol, volvemos (para el index)
        }
        if (self::$userRole === "ADMIN") {
            return; //El rol admin puede ver todo
        }
        if (self::$userRole !== $required_role) {
            Api::redirect("/index.php");
        }
    }

    public static function canSee(string $page): bool
    {
        $role = self::$userRole;

        //Admin puede ver todo
        if ($role === "ADMIN" || $page === "OVERVIEW") {
            return true;
        }

        if ($page === "USERS") {
            return false;
        }

        return true;
    }

    public static function canEdit(string $obj): bool
    {
        $role = self::$userRole;

        //Admin puede hacer todo
        if ($role === "ADMIN") {
            return true;
        }

        return $role === $obj;
    }

    public static function hasRole(string $role): bool
    {
        if ($role === "ANY") {
            return true;
        }

        return self::$userRole === $role;
    }
}
