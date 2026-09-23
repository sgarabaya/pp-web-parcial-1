<?php
require_once "../autoload.php";
Auth::requireRole("STOCK");

function validate(array $data, bool $isCreate = false): Validator
{
    $validator = new Validator($data);

    if ($isCreate) {
        $validator->field("model")->is_required();
        $validator->field("brand")->is_required();
        $validator->field("year")->is_required();
        $validator->field("price")->is_required();
        $validator->field("stock")->is_required();
    } else {
        //Si estamos actualizando el id es obligatorio
        $validator->field("id")->is_required();
    }

    $validator->field("model")->has_max_length(40);
    $validator->field("brand")->has_max_length(40);
    $validator->field("year")->is_int();
    $validator->field("price")->is_numeric();
    $validator->field("stock")->is_int();

    return $validator;
}

function update()
{
    $data = Api::get_request();
    if (empty($data)) {
        throw new Exception(message: Messages::bodyWasEmpty());
    }

    $validator = validate($data, isCreate: false);
    if (!$validator->is_valid()) {
        throw new Exception(message: $validator->get_errors_as_string());
    }

    $vehicleRepository = new VehicleRepository();
    if ($vehicleRepository->updatePartial($data["id"], $data)) {
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

    $validator = validate($data, isCreate: true);
    if (!$validator->is_valid()) {
        throw new Exception(message: $validator->get_errors_as_string());
    }

    $vehicleRepository = new VehicleRepository();
    $vehicle = new Vehicle(
        id: Crypto::uuid4(),
        brand: $data["brand"],
        model: $data["model"],
        price: $data["price"],
        stock: $data["stock"],
        year: $data["year"],
        created: new DateTimeImmutable(datetime: "now"),
    );

    if ($vehicleRepository->create($vehicle)) {
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
        $repository = new VehicleRepository();
        if ($repository->delete((string) $id)) {
            Api::set_message(Messages::operationSuccessful(), "success");
        } else {
            throw new Exception(message: Messages::missingParameter("id"));
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
    Api::redirect("/views/stock.php");
}

?>
