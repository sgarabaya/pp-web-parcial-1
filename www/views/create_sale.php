<?php
require_once "../autoload.php";

//No hay un rol minimo aca
if (!Auth::user()) {
    Api::redirect("/login.php");
}

require_once "../components/header.php";
require_once "../components/navbar.php";
navbar("SALES");

$vehicleRepo = new VehicleRepository();

$vehicles = $vehicleRepo->findAll();

$vehicleMap = [];
foreach ($vehicles as $vehicle) {
    $vehicleMap[$vehicle->id] = [
        sprintf(
            "%s %s (%s) - $%s",
            $vehicle->brand,
            $vehicle->model,
            $vehicle->year,
            $vehicle->price,
        ),
        $vehicle,
    ];
}

$selectedVehicleId = Api::get_query_param("vehicle_id");
$is_selected = fn($id) => $selectedVehicleId === $id ? "selected" : "";

$selected_vehicle = function () use ($selectedVehicleId, $vehicleMap) {
    if ($selectedVehicleId && isset($vehicleMap[$selectedVehicleId])) {
        return $vehicleMap[$selectedVehicleId][1];
    }

    return null;
};
?>
<main class="center">
    <article class="full-width"  style="max-height:100%">
        <header class="flex-separate">
            <h1>Registrar Venta</h1>
        </header>
        <form action="/actions/sale.php" method="POST">
            <input
                type="text" name="METHOD"
                value="POST" class="hidden"
            />
            <fieldset>
                <select name="vehicle_id" required >
                    <?php foreach ($vehicleMap as $vehicleId => $object) { ?>
                        <option
                            value="<?= $vehicleId ?>"
                            <?= $is_selected($vehicleId) ?>
                        ><?= $object[0] ?></option>
                    <?php } ?>
                    </select>
            </fieldset>
            <?php if ($selected_vehicle()): ?>
            <p>Hay <?= $selected_vehicle()->stock ?> disponibles</p>
            <br/>
            <?php endif; ?>
            <fieldset class="inline">
                <input type="number" name="paid_amount" placeholder="Precio" required/>
                <select name="payment_method" placeholder="Metodo de Pago" required>
                    <option value="FINANCED">Financiado</option>
                    <option value="CASH">Contado</option>
                    <option value="EXCHANGE+CASH">Canje+Contado</option>
                    <option value="EXCHANGE+FINANCED">Canje+Financiado</option>
                </select>
            </fieldset>
            <fieldset class="inline">
                <input type="text" placeholder="Cliente"  name="client_name" required />
                <input type="text" placeholder="Contacto" name="client_contact" required />
            </fieldset>
            <footer class="flex-separate">
                <a class="button primary flex-separate" href="/views/stock.php">
                    <i data-lucide="chevron-left"></i>Volver
                </a>
                <input type="submit" class="success" value="Registrar"/>
            </footer>
        </form>
    </article>
</main>

<?php require_once "../components/footer.php"; ?>
