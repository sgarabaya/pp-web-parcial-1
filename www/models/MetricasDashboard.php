<?php
class MetricasDashboard
{
    // Contador de visualizaciones de la sesión
    public static function get_visualizaciones_sesion(): int
    {
        return $_SESSION["visualizaciones"];
    }
    public static function registrarVisualizacion(): void
    {
        if (!isset($_SESSION["visualizaciones"])) {
            $_SESSION["visualizaciones"] = 0;
        }
        $_SESSION["visualizaciones"] += 1;
    }

    // Calcula las ventas
    public static function obtenerTotalRecaudado(PDO $db_connection): float
    {
        $stmt = $db_connection->prepare(
            "SELECT SUM(paid_amount) as total FROM Sales",
        );
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado["total"] ?? 0;
    }

    // Cuenta la cantidad de vehículos disponibles en stock
    public static function obtenerCantidadVehiculosDisponibles(
        PDO $db_connection,
    ): int {
        $stmt = $db_connection->prepare(
            "SELECT SUM(stock) as total FROM Vehicles",
        );
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado["total"] ?? 0;
    }
}
?>
