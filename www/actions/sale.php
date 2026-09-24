<?php
require_once "../autoload.php";

Auth::requireRole("SALES");

function validate(array $data): Validator
{
    $validator = new Validator($data);

    $validator->field("vehicle_id")->is_required();
    $validator->field("paid_amount")->is_required()->is_numeric();
    $validator->field("client_name")->is_required()->has_max_length(40);
    $validator->field("client_contact")->is_required()->has_max_length(40);
    $validator->field("payment_method")->is_required();

    return $validator;
}

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception(Messages::operationFailed());
    }

    $data = Api::get_request();
    if (empty($data)) {
        throw new Exception(message: Messages::bodyWasEmpty());
    }

    $user = Auth::user();
    if (!$user) {
        //Como llegaste aca?
        throw new Exception(Messages::operationFailed());
    }

    $validator = validate($data);
    if (!$validator->is_valid()) {
        throw new Exception(message: $validator->get_errors_as_string());
    }

    $salesRepository = new SaleRepository();

    $salesRepository->registerSale(
        new Sale(
            id: Crypto::uuid4(),
            userId: $user->getId(),
            vehicleId: $data["vehicle_id"],
            paidAmount: $data["paid_amount"],
            clientName: $data["client_name"],
            clientContact: $data["client_contact"],
            paymentMethod: $data["payment_method"],
            created: new DateTimeImmutable(datetime: "now"),
        ),
    );

    Api::set_message(Messages::operationSuccessful(), "success");
} catch (Exception $e) {
    Api::set_error_message($e->getMessage());
} finally {
    Api::redirect("/views/sales.php");
}

?>
