<?php
require_once "../autoload.php";

Auth::requireRole("ADMIN");

require_once "../components/header.php";
require_once "../components/navbar.php";
navbar("USERS");
?>
<main>
    <article class="full-width">
        <header class="flex-separate">
            <h1>Empleados</h1>
        <button id="button-create" class="primary">Crear Usuario</button>
        </header>
        <table>
            <thead>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Email</th>
                <th>Role</th>
                <th>Actions</th>
            </thead>
            <tbody>
            <?php
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

            $userRepo = new UserRepository();
            $users = $userRepo->findAll();
            foreach ($users as $user) { ?>
                <tr>
                    <td><?= $user->name ?></td>
                    <td><?= $user->lastName ?></td>
                    <td><?= $user->email ?></td>
                    <td><?= rolePill($user->role) ?></td>
                    <td class="actions">
                        <img class="tiny" src="/public/edit.png"/>
                        <img class="tiny" src="/public/trash.png"/>
                    </td>
                </tr>
                <?php }
            ?>
            </tbody>
        </table>
</article>
</main>

<dialog id="create-dialog">
    <article>
        <header>
            <h3>Usuario</h3>
        </header>
        <main>
            <form>
                <input id="user_name" type="text" placeholder="Nombre" />
                <input id="user_last_name"  type="text" placeholder="Apellido" />
                <input id="user_email" type="text" placeholder="Email" />
                <select id="user_role" name="role">
                    <option value="SALES">Ventas</option>
                    <option value="STOCK">Inventario</option>
                    <option value="ADMIN">Administrador</option>
                </select>
            </form>
        </main>
        <footer style="display:flex;justify-content:space-between">
            <button id="button-cancel" class="bad">Cancelar</button>
            <button id="button-save">Guardar</button>
        </footer>
    </article>
</dialog>

<script>
const $ = id => document.getElementById(id);
  document.addEventListener('DOMContentLoaded', ()=> {
    const dialog = $('create-dialog');
    const controls = {
        name: $('user_name'),
        last_name: $('user_last_name'),
        email: $('user_email'),
        role: $('user_role'),
    };
    const data = {};

    function reset(){
        controls.name.value = '';
        controls.last_name.value = '';
        controls.email.value = '';
        controls.role.value = 'SALES';
    }

    controls.name.oninput = ev =>{ data['name'] = ev.target.value; };
    controls.last_name.oninput = ev =>{ data['last_name'] = ev.target.value; };
    controls.email.oninput = ev =>{ data['email'] = ev.target.value; };
    controls.role.oninput = ev =>{ data['role'] = ev.target.value; };

    $('button-create').onclick = ev => {
      dialog.open = true;
      $('button-cancel').onclick = ev => {
          ev.preventDefault();
          reset();
          dialog.open = false;
      };
    };
});
</script>
<?php require_once "../components/footer.php"; ?>
