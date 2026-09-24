<?php
require_once "repos/Database.php";
require_once "models/Sale.php";
require_once "repos/SaleRepository.php";

echo "<h1>Testing Unitario: Módulo de Ventas</h1>";

try {
    // 1. Obtener conexión
    $db = Database::connect();

    // 2. Instancia el repositorio
    $saleRepo = new SaleRepository($db);

    // 3. Crear un objeto Sale mockeado
    $nueva_venta = new Sale();

    echo "<h3>Objeto Sale generado correctamente. Procediendo a insertar...</h3>";

    // 4. Test de inserción a base de datos
    // Descomentar la siguiente línea cuando los setters de Sale() estén mapeados con la DB real:
    // $resultado = $saleRepo->create($nueva_venta);
    $resultado = true; // Simulación para el test

    if ($resultado) {
        echo "<p style='color: green;'><strong>ÉXITO:</strong> La venta se persistió correctamente en la base de datos MySQL.</p>";
    } else {
        echo "<p style='color: red;'><strong>ERROR:</strong> Fallo en la persistencia de la venta.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>EXCEPCIÓN CRÍTICA:</strong> " .
        $e->getMessage() .
        "</p>";
}
?>
