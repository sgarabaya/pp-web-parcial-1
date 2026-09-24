<?php
require_once "autoload.php";

// Validar autenticación
$user = Auth::user();
if (!$user) {
    Api::redirect("/login.php");
}

// Iniciar conexión
$db = Database::connect();

// Ejecutar método estático
MetricasDashboard::registrarVisualizacion();

// Identificar rol
$es_admin = Auth::hasRole("ADMIN");

// Obtener métricas mediante métodos estáticos
$total_recaudado = MetricasDashboard::obtenerTotalRecaudado($db);
$stock_actual = MetricasDashboard::obtenerCantidadVehiculosDisponibles($db);

require_once "components/header.php";
require_once "components/navbar.php";
navbar("OVERVIEW");

$salesRepo = new SaleRepository();
?>

<main class="full-width max-height" style="overflow:scroll">
    <article class="full-width">
        <header>
            <h1>Panel de Control</h1>
        </header>
        <p>Bienvenido!</p>
       <p>Visualizaciones de este panel durante su sesión: <strong><?php echo MetricasDashboard::get_visualizaciones_sesion(); ?></strong></p>
    </article>

    <div class="flex-separate flex-stretch full-width">
        <article class="full-width">
            <header>
                <h2>Stock de Vehículos</h2>
            </header>
            <p><strong><?php echo $stock_actual; ?> Unidades Disponibles</strong></p>
            <p>Vehiculos listos para la venta en la agencia.</p>
        </article>

        <!-- Vista Exclusiva para Administradores -->
        <?php if ($es_admin): ?>
            <article class="full-width">
                <header>
                    <h2>Finanzas Generales</h2>
                </header>
                <p><strong>$<?php echo number_format(
                    $total_recaudado,
                    2,
                ); ?> Recaudados</strong></p>
                <p>Total histórico de ventas registradas por todos los empleados.</p>
            </article>
        <?php else: ?>
            <article class="full-width">
                <header>
                    <h2>Mis Accesos Rápidos</h2>
                </header>
                <div class="flex-separate">
                    <div class="mini-card">
                        <h3>Área de Ventas</h3>
                        <p><a href="/views/create_sale.php" class="button primary">Registrar Nueva Venta</a></p>
                    </div>
                    <div class="mini-card">
                        <h3>Inventario</h3>
                        <p><a href="/views/stock.php" class="button primary">Ver Inventario Disponible</a></p>
                    </div>
                </div>
            </article>
        <?php endif; ?>
    </div>
    <article class="full-width">
        <header>
            <h2>Estadisticas</h2>
        </header>
        <div class="charts-container">
            <canvas class="chart" id="trend-chart"></canvas>
            <canvas class="chart" id="vehicles-chart"></canvas>
            <canvas class="chart" id="employee-chart"></canvas>
            <canvas class="chart" id="stock-chart"></canvas>
        </div>
    </article>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
<script src="/public/charts.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', ()=> {
    createCharts(<?= json_encode($salesRepo->fetchSalesOverview()) ?>);
  });
</script>
<?php require_once "components/footer.php"; ?>
