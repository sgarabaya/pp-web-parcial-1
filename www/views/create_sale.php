<?php
require_once "../autoload.php";

Auth::ensureLoggedIn(); //No hay un rol minimo aca

require_once "../components/header.php";
require_once "../components/navbar.php";
navbar("SALES");

$vehicleRepo = new VehicleRepository();

$vehicles = $vehicleRepo->findAll();

$vehicleMap = [];
foreach ($vehicles as $_ => $vehicle) {
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

// $salesRepo = new SaleRepository();
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
                <input type="number" name="price" placeholder="Precio" />
                <select name="payment_option" placeholder="Forma de pago">
                    <option>Contado</option>
                    <option>Financiado</option>
                    <option>Canje+Contado</option>
                    <option>Canje+Financiado</option>
                </select>
            </fieldset>
            <fieldset class="inline">
                <input type="text" placeholder="Cliente" />
                <input type="text" placeholder="Contacto" />
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
