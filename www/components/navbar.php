<?php
function choose($a, $b)
{
    return $a === $b ? " selected" : "";
}

function navbar(string $selected)
{
    $entries = [
        "OVERVIEW" => ["/index.php", "/public/overview.png", "Resumen"],
        "SALES" => ["/views/sales.php", "/public/sales.png", "Ventas"],
        "STOCK" => ["/views/stock.php", "/public/stock.png", "Inventario"],
        "USERS" => ["/views/users.php", "/public/employees.png", "Empleados"],
    ]; ?>
    <nav class="sidebar" id="sidebar">
        <div>
            <div class="logo">
                <a href="/index.php">Ruta 9</a>
                <span id="sidebar-toggle" role="close"></span>
            </div>
            <?php foreach ($entries as $key => $values) {
                if (Auth::canSee($key)) {
                    echo sprintf(
                        '<a href="%s" class="sidebar-entry%s"><img src="%s" /><span>%s</span></a>',
                        $values[0],
                        choose($selected, $key),
                        $values[1],
                        $values[2],
                    );
                }
            } ?>
        </div>
        <a href="/logout.php" class="logout">
            <span> <?= Auth::getName() ?></span>
            <img class="tiny" src="/public/logout.png" />
        </a>
    </nav>
<?php
} ?>
