<?php
function choose($a, $b)
{
    return $a === $b ? "selected" : "";
}

function navbar(string $selected)
{
    ?>
<nav class="sidebar" id="sidebar">
      <div>
          <div class="logo">
              <a href="/index.php">Ruta 9</a>
              <span id="sidebar-toggle" role="close"></span>
          </div>
          <a href="/index.php" class="sidebar-entry <?= choose(
              $selected,
              "OVERVIEW",
          ) ?>">
              <img src="/public/overview.png" />
              <span>Resumen</span>
          </a>
          <a href="/views/sales.php" class="sidebar-entry <?= choose(
              $selected,
              "SALES",
          ) ?>">
              <img src="/public/sales.png" />
              <span>Ventas</span>
          </a>
          <a href="/views/stock.php" class="sidebar-entry <?= choose(
              $selected,
              "STOCK",
          ) ?>">
              <img src="/public/stock.png" />
              <span>Inventario</span>
          </a>
          <a href="/views/users.php" class="sidebar-entry <?= choose(
              $selected,
              "USERS",
          ) ?>">
              <img src="/public/employees.png" />
              <span>Empleados</span>
          </a>
      </div>
      <a href="/logout.php" class="logout">
          <span>S. Garabaya</span>
          <img class="tiny" src="/public/logout.png" />
      </a>
  </nav>

  <?php
} ?>
