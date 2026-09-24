<?php
require_once "../autoload.php";

$user = Auth::user(); //No hay un rol minimo aca
if (!$user) {
    Api::redirect("/login.php");
}

require_once "../components/header.php";
require_once "../components/navbar.php";
navbar("STOCK");

$vehiclesRepo = new VehicleRepository();
?>
<main>
    <article class="full-width" style="max-height:100%">
        <header class="flex-separate">
            <h1>Inventario</h1>
            <?php if ($user->canEdit("STOCK")): ?>
            <a class="button primary" href="/views/edit_stock.php">Agregar Vehiculo</a>
            <?php endif; ?>
        </header>
        <div class="table-container">
            <table>
                <thead>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Año</th>
                    <th>Precio</th>
                    <th>Inventario</th>
                    <th>Acciones</th>
                </thead>
                <tbody>
                    <?php foreach ($vehiclesRepo->findAll() as $vehicle) { ?>
                        <?= $vehicle->stock > 0
                            ? "<tr>"
                            : '<tr class="error">' ?>
                            <td><?= $vehicle->brand ?></td>
                            <td><?= $vehicle->model ?></td>
                            <td><?= $vehicle->year ?></td>
                            <td>$<?= $vehicle->price ?></td>
                            <td><?= $vehicle->stock ?></td>
                            <td class="actions">
                                <?php if ($user->canEdit("SALES")): ?>
                                    <a href="/views/create_sale.php?vehicle_id=<?= $vehicle->id ?>">
                                        <i data-lucide="circle-dollar-sign" style="color:var(--success)"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($user->canEdit("STOCK")): ?>
                                    <a href="/views/edit_stock.php?id=<?= $vehicle->id ?>">
                                        <i data-lucide="square-pen"></i>
                                    </a>
                                    <a href="#" onclick="deleteVehicle('<?= $vehicle->id ?>')">
                                        <i data-lucide="trash" style="color:var(--error)"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php } ?>
                </tbody>
            </table>
        </div>
    </article>
    <dialog id="confirm-dialog">
        <header>
            <h2>Eliminar vehiculo</h2>
        </header>
            <p class="error-text">Esta operacion no tiene vuelta atras.</p>
            Desea continuar?
        <footer>
            <button class="primary">Cancelar</button>
            <form action="/actions/stock.php" method="POST">
                <input type="text" name="id" class="hidden" />
                <input type="text" name="METHOD" value="DELETE" class="hidden" />
                <button class="success">Aceptar</button>
            </form>
        </footer>
    </dialog>
</main>

<script>
const $ = s => document.querySelector(s);
function deleteVehicle(id) {
  const dialog = $('#confirm-dialog');
  dialog.open = true;
  $('#confirm-dialog button[class=primary]').onclick= ev => { ev.preventDefault(); dialog.open = false; };
  $('#confirm-dialog input[name="id"]').value = id;
}
</script>

<?php require_once "../components/footer.php"; ?>
