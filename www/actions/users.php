<?php
require_once "../autoload.php";
Auth::requireRole("ADMIN");

function validateUser(array $data, bool $isCreate = false): Validator
{
    $validator = new Validator($data);

    if ($isCreate) {
        $validator->field("name")->is_required();
        $validator->field("last_name")->is_required();
        $validator->field("email")->is_required();
        $validator->field("password")->is_required();
        $validator->field("role")->is_required();
    } else {
        //Si estamos actualizando el usuario, el id es obligatorio
        $validator->field("id")->is_required();
    }

    $validator->field("name")->has_max_length(40);
    $validator->field("last_name")->has_max_length(40);
    $validator->field("email")->is_email()->has_max_length(40);
    $validator
        ->field("role")
        ->custom(
            fn($v) => in_array($v, ["ADMIN", "STOCK", "SALES"]),
            Messages::roleCanBe(),
        );

    return $validator;
}

function update()
{
    $data = Api::get_request();
    if (empty($data)) {
        throw new Exception(message: Messages::bodyWasEmpty());
    }

    $validator = validateUser($data, isCreate: false);
    if (!$validator->is_valid()) {
        throw new Exception(message: $validator->get_errors_as_string());
    }

    $userRepository = new UserRepository();
    if ($userRepository->update($data["id"], $data)) {
        Api::set_message(Messages::operationSuccessful(), "success");
    } else {
        throw new Exception(message: Messages::operationFailed());
    }
}

function create()
{
    $data = Api::get_request();
    if (empty($data)) {
        throw new Exception(message: Messages::bodyWasEmpty());
    }

    $validator = validateUser($data, isCreate: true);
    if (!$validator->is_valid()) {
        throw new Exception(message: $validator->get_errors_as_string());
    }

    $userRepository = new UserRepository();
    $user = User::create(
        id: Crypto::uuid4(),
        name: $data["name"],
        lastName: $data["last_name"],
        email: $data["email"],
        role: $data["role"],
        passwordHash: Crypto::passwordHash($data["password"]),
        created: new DateTimeImmutable(datetime: "now"),
    );

    if ($userRepository->create($user)) {
        Api::set_message(Messages::operationSuccessful(), "success");
    } else {
        throw new Exception(message: Messages::operationFailed());
    }
}

function delete()
{
    $id = Api::safe_get($_POST, "id");

    if (!$id || empty($id)) {
        throw new Exception(message: Messages::missingParameter("id"));
    } else {
        $userRepository = new UserRepository();
        if ($userRepository->delete((string) $id)) {
            Api::set_message(Messages::operationSuccessful(), "success");
        } else {
            throw new Exception(message: Messages::operationFailed());
        }
    }
}

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception(Messages::operationFailed());
    }

    // workaround porque HTML es estupido (por que no hay DELETE en form methods??)
    $method = Api::safe_get($_POST, "METHOD");
    switch ($method) {
        case "POST":
            create();
            break;
        case "PUT":
            update();
            break;
        case "DELETE":
            delete();
            break;
    }
} catch (Exception $e) {
    Api::set_error_message($e->getMessage());
} finally {
    Api::redirect("/views/users.php");
}

?>
