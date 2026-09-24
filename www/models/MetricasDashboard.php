<?php
class MetricasDashboard
{
    // Propiedad 1: Contador de visualizaciones de la sesión
    public static $visualizaciones_sesion = 0;

    // Método 1: Calcula las ventas
    public static function obtenerTotalRecaudado(PDO $db_connection): float
    {
        $stmt = $db_connection->prepare(
            "SELECT SUM(paid_amount) as total FROM Sales",
        );
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado["total"] ?? 0;
    }

    // Método 2: Cuenta la cantidad de vehículos disponibles en stock
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

    // Método 3: Registrar nueva visualización
    public static function registrarVisualizacion(): void
    {
        self::$visualizaciones_sesion++;
    }
}
?>
