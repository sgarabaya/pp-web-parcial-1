<?php
require_once "../autoload.php";

Auth::requireRole("STOCK");

require_once "../components/header.php";
require_once "../components/navbar.php";
navbar("STOCK");

function get(array $obj, string $key): string
{
    return isset($obj[$key]) ? $obj[$key] : "";
}

$vehiclesRepo = new VehicleRepository();
$vehicle = [];

$isUpdate = false;
$id = Api::get_query_param("id");
if ($id && !empty($id)) {
    $isUpdate = true;
    $vehicle = $vehiclesRepo->findById($id)->mapTo();
}
?>
<main class="center">
    <article style="min-width:600px">
    <header>
        <h2>
            <? if($isUpdate): ?>Modificar<? else: ?>Crear<? endif ?> Vehiculo</h2>
    </header>
    <form action="/actions/stock.php" method="POST">
        <input
            type="text" name="METHOD"
            value="<?= $isUpdate ? "PUT" : "POST" ?>"
            class="hidden"
        />
        <? if($isUpdate): ?>
            <input
                type="text" name="id"
                value="<?= $id ?>"
                class="hidden"
            />
        <? endif ?>
        <fieldset>
            <input
                name="brand" type="text"
                placeholder="Marca"
                value="<?= get($vehicle, "brand") ?>"
                required  />
        </fieldset>
        <fieldset>
            <input
                name="model" type="text"
                placeholder="Modelo"
                value="<?= get($vehicle, "model") ?>"
                required  />
        </fieldset>
        <fieldset>
            <input
                name="year" type="number"
                min="1908" max="2026"
                placeholder="Año"
                value="<?= get($vehicle, "year") ?>"
                required  />
        </fieldset>
        <fieldset>
            <input
                name="price" type="number"
                min="0" step="0.001"
                placeholder="Precio"
                value="<?= get($vehicle, "price") ?>"
                required  />
        </fieldset>
        <fieldset>
            <input
                name="stock" type="number"
                min="1"
                placeholder="Inventario"
                value="<?= get($vehicle, "stock") ?>"
                required  />
        </fieldset>
        <footer class="flex-separate">
            <a class="button primary flex-separate" href="/views/stock.php">
                <img class="tiny" src="/public/back.svg"/>Volver
            </a>
            <input type="submit" class="success" value="Guardar"/>
        </footer>
    </form>
</article>
</main>
<?php require_once "../components/footer.php"; ?>
