<?php
function choose($a, $b)
{
    return $a === $b ? " selected" : "";
}

function navbar(string $selected)
{
    $user = Auth::user();
    if (!$user) {
        Api::redirect("/login.php"); //Como llegaste aca?
    }

    $entries = [
        "OVERVIEW" => ["/index.php", "chart-no-axes-combined", "Resumen"],
        "SALES" => ["/views/sales.php", "handshake", "Ventas"],
        "STOCK" => ["/views/stock.php", "shelving-unit", "Inventario"],
        "USERS" => ["/views/users.php", "user-group", "Empleados"],
    ];
    ?>
    <nav class="sidebar" id="sidebar">
        <div>
            <div class="logo">
                <a href="/index.php">Ruta 9</a>
            </div>
            <?php foreach ($entries as $key => $values) {
                if ($user->canSee($key)) {
                    echo sprintf(
                        '<a href="%s" class="sidebar-entry%s"><i data-lucide="%s"></i><span>%s</span></a>',
                        $values[0],
                        choose($selected, $key),
                        $values[1],
                        $values[2],
                    );
                }
            } ?>
        </div>
        <a href="/logout.php" class="logout">
            <span> <?= $user->displayName() ?></span>
            <i data-lucide="log-out"></i>
        </a>
    </nav>
<?php
} ?>
