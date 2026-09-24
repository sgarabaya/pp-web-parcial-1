<?php

abstract class Auth
{
    private static $userId;
    private static $userRole;
    private static $userName;
    private static ?User $user = null;

    protected function __construct() {}

    public static function load(): void
    {
        session_start();
        self::$userId = Api::safe_get($_SESSION, "user.id");
        self::$userRole = Api::safe_get($_SESSION, "user.role");
        self::$userName = Api::safe_get($_SESSION, "user.name");
    }

    public static function getUserId(): string
    {
        return self::$userId;
    }

    public static function getName(): string
    {
        return self::$userName;
    }

    public static function user(): ?User
    {
        if (self::$user === null && self::$userId) {
            self::$user = (new UserRepository())->findById(self::$userId);
        }
        return self::$user;
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

        if (
            $user &&
            Crypto::passwordVerify($password, $user->getPasswordHash())
        ) {
            $_SESSION["user.id"] = $user->getId();
            $_SESSION["user.role"] = $user->getRole();
            $_SESSION["user.name"] = sprintf(
                "%s.%s",
                substr($user->getName(), 0, 1),
                $user->getLastName(),
            );

            self::$user = $user;

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
        $user = self::user();
        if (!$user) {
            Api::redirect("/login.php");
        }

        if ($required_role === "ANY") {
            return; //Si no hay un requerimiento de rol, volvemos (para el index)
        }

        if (!$user->satisfies($required_role)) {
            Api::redirect("/index.php");
        }
    }

    public static function canSee(string $page): bool
    {
        return self::user()?->canSee($page) ?? false;
    }

    public static function hasRole(string $role): bool
    {
        return self::$userRole === $role;
    }

    public static function canEdit(string $obj): bool
    {
        return self::user()?->canEdit($obj) ?? false;
    }
}
