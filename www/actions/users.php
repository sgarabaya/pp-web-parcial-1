<?php
require_once "../autoload.php";

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

    $validator->field("name")->has_max_length(256);
    $validator->field("last_name")->has_max_length(256);
    $validator->field("email")->is_email();
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
        Api::respond_json(["error" => Messages::bodyWasEmpty()], 400);
    }

    $validator = validateUser($data, isCreate: false);
    if (!$validator->is_valid()) {
        Api::respond_json(["errors" => $validator->get_errors()], 400);
    }

    $userRepository = new UserRepository();
    if ($userRepository->updatePartial($data["id"], $data)) {
        Api::respond_json(["message" => Messages::operationSuccessful()], 200);
    } else {
        Api::respond_json(["error" => Messages::operationFailed()], 500);
    }
}

function create()
{
    $data = Api::get_request();
    if (empty($data)) {
        Api::respond_json(["error" => Messages::bodyWasEmpty()], 400);
    }

    $validator = validateUser($data, isCreate: true);
    if (!$validator->is_valid()) {
        Api::respond_json(["errors" => $validator->get_errors()], 400);
    }

    $userRepository = new UserRepository();
    $user = new User(
        id: Crypto::uuid4(),
        name: $data["name"],
        lastName: $data["last_name"],
        email: $data["email"],
        role: $data["role"],
        passwordHash: Crypto::passwordHash($data["password"]),
        created: new DateTimeImmutable(datetime: "now"),
    );

    if ($userRepository->create($user)) {
        Api::respond_json(["message" => Messages::operationSuccessful()], 200);
    } else {
        Api::respond_json(["error" => Messages::operationFailed()], 500);
    }
}

function delete()
{
    $id = Api::get_query_param("id");

    if (!$id || empty($id)) {
        Api::respond_json(["error" => Messages::missingParameter("id")], 400);
    }

    $userRepository = new UserRepository();
    if ($userRepository->delete((string) $id)) {
        Api::respond_json(["message" => Messages::operationSuccessful()], 200);
    } else {
        Api::respond_json(["error" => Messages::operationFailed()], 500);
    }
}

function get()
{
    $userRepository = new UserRepository();

    $id = Api::get_query_param("id");
    if ($id !== null && !empty($id)) {
        $user = $userRepository->findById($id);
        if ($user instanceof User) {
            Api::respond_json($user->serialize());
        } else {
            Api::respond_json(["error" => Messages::notFound()], 404);
        }
    }

    $email = Api::get_query_param("email");
    if ($email !== null && !empty($email)) {
        $user = $userRepository->findByEmail($email);
        if ($user instanceof User) {
            Api::respond_json($user->serialize());
        } else {
            Api::respond_json(["error" => Messages::notFound()], 404);
        }
    }
    $users = array_map(fn($u) => $u->serialize(), $userRepository->findAll());
    Api::respond_json($users);
}

switch ($_SERVER["REQUEST_METHOD"]) {
    case "GET":
        get();
        break;
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
?>
