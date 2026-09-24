<?php
require_once "../autoload.php";

Auth::requireRole("ADMIN");

require_once "../components/header.php";
require_once "../components/navbar.php";
navbar("USERS");

$userRepo = new UserRepository();

function rolePill($role)
{
    switch ($role) {
        case "ADMIN":
            echo '<span class="pill admin">Admin</span>';
            break;
        case "STOCK":
            echo '<span class="pill stock">Inventario</span>';
            break;
        case "SALES":
            echo '<span class="pill sales">Ventas</span>';
            break;
        default:
            echo '<span class="pill">?</span>'; //como llegaste aca??
            break;
    }
}
function get_role_prio(User $user)
{
    return ["ADMIN" => 0, "STOCK" => 1, "SALES" => 2][$user->getRole()];
}

$users = $userRepo->findAll();
usort($users, fn($a, $b) => get_role_prio($a) - get_role_prio($b));
?>
<main>
    <article class="full-width" style="max-height:100%">
        <header class="flex-separate">
            <h1>Empleados</h1>
            <a class="button primary" href="/views/edit_user.php">Crear Usuario</a>
        </header>
        <div class="table-container">
            <table>
                <thead>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Acciones</th>
                </thead>
                <tbody>
                    <?php foreach ($users as $user) { ?>
                        <tr>
                            <td><?= $user->getName() ?></td>
                            <td><?= $user->getLastName() ?></td>
                            <td><?= $user->getEmail() ?></td>
                            <td><?= rolePill($user->getRole()) ?></td>
                            <td class="actions">
                                <a href="/views/edit_user.php?id=<?= $user->getId() ?>">
                                    <i data-lucide="square-pen"></i>
                                </a>
                                <a href="#" onclick="deleteUser('<?= $user->getId() ?>')"><i data-lucide="trash"></i></a>
                            </td>
                        </tr>
                        <?php } ?>
                </tbody>
            </table>
        </div>
    </article>
    <dialog id="confirm-dialog">
        <header>
            <h2>Eliminar usuario</h2>
        </header>
            <p class="error-text">Esta operacion no tiene vuelta atras.</p>
            Desea continuar?
        <footer>
            <button class="primary">Cancelar</button>
            <form action="/actions/users.php" method="POST">
                <input type="text" name="id" class="hidden" />
                <input type="text" name="METHOD" value="DELETE" class="hidden" />
                <button class="success">Aceptar</button>
            </form>
        </footer>
    </dialog>
</main>

<script>
const $ = s => document.querySelector(s);
function deleteUser(userId ) {
  const dialog = $('#confirm-dialog');
  dialog.open = true;
  $('#confirm-dialog button[class=primary]').onclick= ev => { ev.preventDefault(); dialog.open = false; };
  $('#confirm-dialog input[name="id"]').value = userId;
}
</script>
<?php require_once "../components/footer.php"; ?>
