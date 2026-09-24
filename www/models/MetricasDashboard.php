<?php
class MetricasDashboard
{
    // Propiedad 1: Contador de visualizaciones de la sesión
    public static $visualizaciones_sesion = 0;

    // Método 1: Calcula las ventas
    public static function obtenerTotalRecaudado($db_connection)
    {
        $stmt = $db_connection->prepare(
            "SELECT SUM(amount) as total FROM sales",
        );
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado["total"] ?? 0;
    }

    // Método 2: Cuenta la cantidad de vehículos disponibles en stock
    public static function obtenerCantidadVehiculosDisponibles($db_connection)
    {
        $stmt = $db_connection->prepare(
            "SELECT COUNT(*) as total FROM vehicles WHERE status = 'disponible'",
        );
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado["total"] ?? 0;
    }

    // Método 3: Registrar nueva visualización
    public static function registrarVisualizacion()
    {
        self::$visualizaciones_sesion++;
    }
}
?>
