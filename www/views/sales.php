<?php
require_once "../autoload.php";

Auth::requireRole("SALES");

require_once "../components/header.php";
require_once "../components/navbar.php";
navbar("SALES");

function map_payment_method(string $method)
{
    switch ($method) {
        case "CASH":
            return "Efectivo";
        case "FINANCED":
            return "Financiado";
        case "EXCHANGE+CASH":
            return "Canje y Efectivo";
        case "EXCHANGE+FINANCED":
            return "Canje y Financiado";
    }
}

$repo = new SaleRepository();
?>
<main>
    <article class="full-width" style="max-height:100%">
        <header class="flex-separate">
            <h1>Ventas</h1>
            <a class="button primary" href="/views/create_sale.php">Registrar Venta</a>
        </header>
        <div class="table-container">
            <table>
                <thead>
                    <th>Empleado</th>
                    <th>Vehiculo</th>
                    <th>Monto</th>
                    <th>Cliente</th>
                    <th>Metodo de Pago</th>
                    <th>Fecha</th>
                </thead>
                <tbody>
                    <?php foreach ($repo->fetchDetails() as $sale) { ?>
                        <tr>
                            <td><?= $sale->user ?></td>
                            <td><?= $sale->vehicle ?></td>
                            <td>$<?= $sale->paidAmount ?></td>
                            <td>
                                <p><?= $sale->clientName ?></p>
                                (<?= $sale->clientContact ?>)
                            </td>
                            <td><?= map_payment_method(
                                $sale->paymentMethod,
                            ) ?></td>
                            <td>
                                <span class="pill">
                                    <?= $sale->created->format("Y/m/d") ?>
                                </span>
                            </td>
                        </tr>
                     <?php } ?>
                </tbody>
            </table>
        </div>
    </article>
</main>

<?php require_once "../components/footer.php"; ?>
