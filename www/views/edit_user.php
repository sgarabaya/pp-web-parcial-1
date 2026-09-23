<?php
require_once "../autoload.php";

Auth::requireRole("ADMIN");

require_once "../components/header.php";
require_once "../components/navbar.php";
navbar("USERS");

$roles = [
    "SALES" => "Ventas",
    "STOCK" => "Inventario",
    "ADMIN" => "Admin",
];

function get(array $obj, string $key): string
{
    return isset($obj[$key]) ? $obj[$key] : "";
}

$userRepo = new UserRepository();
$user = ["role" => "SALES"];

$isUpdate = false;
$userId = Api::get_query_param("id");
if ($userId && !empty($userId)) {
    $isUpdate = true;
    $user = $userRepo->findById($userId)->mapTo();
}
?>
<main class="center">
    <article style="min-width:600px">
    <header>
        <h2>
            <? if($isUpdate): ?>Modificar<? else: ?>Crear<? endif ?> Usuario</h2>
    </header>
    <form action="/actions/users.php" method="POST">
        <input
            type="text" name="METHOD"
            value="<?= $isUpdate ? "PUT" : "POST" ?>"
            class="hidden"
        />
        <? if($isUpdate): ?>
            <input
                type="text" name="id"
                value="<?= $userId ?>"
                class="hidden"
            />
        <? endif ?>
        <fieldset>
            <input
                name="name" type="text"
                placeholder="Nombre"
                value="<?= get($user, "name") ?>"
                required  />
        </fieldset>
        <fieldset>
            <input
                name="last_name"  type="text"
                placeholder="Apellido"
                value="<?= get($user, "last_name") ?>"
                required />
        </fieldset>
        <fieldset>
            <input
                name="email" type="text"
                placeholder="Email"
                value="<?= get($user, "email") ?>"
                required />
        </fieldset>
        <fieldset>
            <select name="role" required >
                <?php foreach ($roles as $roleName => $roleValue) { ?>
                    <option
                        value="<?= $roleName ?>"
                        <?= get($user, "role") === $roleName
                            ? "selected"
                            : "" ?>
                    ><?= $roleValue ?></option>
                <?php } ?>
                </select>
        </fieldset>
        <fieldset>
            <input name="password" type="password" placeholder="Contraseña" />
        </fieldset>
        <footer class="flex-separate">
            <a class="button primary flex-separate" href="/views/users.php">
                <img class="tiny" src="/public/back.svg"/>Volver
            </a>
            <input type="submit" class="success" value="Guardar"/>
        </footer>
    </form>
</article>
</main>
<?php require_once "../components/footer.php"; ?>
