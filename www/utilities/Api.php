<?php
abstract class Api
{
    public static function set_message(string $message, string $type): void
    {
        $_SESSION["message"] = $message;
        $_SESSION["message_type"] = $type;
    }

    public static function set_error_message(string $message): void
    {
        $_SESSION["message"] = $message;
        $_SESSION["message_type"] = "error";
    }

    public static function redirect(string $url): void
    {
        header("Location: $url");
        exit();
    }

    public static function get_request(): mixed
    {
        $body = [];
        foreach ($_POST as $key => $value) {
            $body[$key] = $value;
        }
        return $body;
    }

    public static function get_query_param(string $name): ?string
    {
        return Api::safe_get($_GET, $name);
    }

    /** @param array $request */
    public static function safe_get(array $request, string $key): ?string
    {
        return isset($request[$key]) ? $request[$key] : null;
    }
}
?>
