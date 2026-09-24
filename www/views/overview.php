<?php
session_start();

// Validar autenticación
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

require_once "../repos/Database.php";
require_once "../models/MetricasDashboard.php";

// Iniciar conexión
$db = Database::getInstance()->getConnection();

// Ejecutar método estático
MetricasDashboard::registrarVisualizacion();

// Identificar rol
$es_admin =
    isset($_SESSION["user_role"]) && $_SESSION["user_role"] === "Administrador";

// Obtener métricas mediante métodos estáticos
$total_recaudado = MetricasDashboard::obtenerTotalRecaudado($db);
$stock_actual = MetricasDashboard::obtenerCantidadVehiculosDisponibles($db);

require_once "../components/header.php";
require_once "../components/navbar.php";
?>

<main class="container mt-4">
    <h2>Panel de Control (Overview)</h2>
    <p>Bienvenido. Visualizaciones de este panel durante su sesión: <strong><?php echo MetricasDashboard::$visualizaciones_sesion; ?></strong></p>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card text-white bg-primary mb-3">
                <div class="card-header">Stock de Vehículos</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $stock_actual; ?> Unidades Disponibles</h5>
                    <p class="card-text">Vehículos listos para la venta en la agencia.</p>
                </div>
            </div>
        </div>

        <?php if ($es_admin): ?>
        <!-- Vista Exclusiva para Administradores -->
        <div class="col-md-6">
            <div class="card text-white bg-success mb-3">
                <div class="card-header">Finanzas Generales</div>
                <div class="card-body">
                    <h5 class="card-title">$<?php echo number_format(
                        $total_recaudado,
                        2,
                    ); ?> Recaudados</h5>
                    <p class="card-text">Total histórico de ventas registradas por todos los empleados.</p>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Vista para Empleados -->
        <div class="col-md-6">
            <div class="card text-white bg-info mb-3">
                <div class="card-header">Mis Accesos Rápidos</div>
                <div class="card-body">
                    <h5 class="card-title">Área de Ventas</h5>
                    <a href="create_sale.php" class="btn btn-light">Registrar Nueva Venta</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once "../components/footer.php"; ?>
