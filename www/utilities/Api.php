<?php
abstract class Api
{
    public static function respond_json(mixed $obj, int $status = 200): void
    {
        http_response_code($status);
        header("Content-Type: application/json; charset=utf-8");
        die(json_encode($obj));
    }

    public static function get_request(): mixed
    {
        $body = [];
        if ($_SERVER["CONTENT_TYPE"] == "application/json") {
            $input = file_get_contents("php://input");
            if ($input) {
                $json = json_decode($input);

                $body["is_json"] = true;

                foreach ($json as $key => $value) {
                    $body[$key] = $value;
                }
            }
        } else {
            foreach ($_POST as $key => $value) {
                $body[$key] = $value;
            }
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
