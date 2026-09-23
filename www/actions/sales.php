<?php
require_once "../autoload.php";

Auth::canEdit("SALES");

function validate(array $data): Validator
{
    $validator = new Validator($data);

    $validator->field("model")->is_required();
    $validator->field("brand")->is_required();
    $validator->field("year")->is_required();
    $validator->field("price")->is_required();
    $validator->field("stock")->is_required();

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

    $validator = validate($data, isCreate: true);
    if (!$validator->is_valid()) {
        throw new Exception(message: $validator->get_errors_as_string());
    }

    $salesRepository = new SaleRepository();

    $salesRepository->registerSale(
        new Sale(
            id: Crypto::uuid4(),
            userId: $data["user_id"],
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
