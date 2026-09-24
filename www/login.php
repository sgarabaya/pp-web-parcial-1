<?php
require_once "autoload.php";

if (!empty($_SESSION["user.id"])) {
    Api::redirect("/index.php");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (Auth::login()) {
        Api::redirect("/index.php");
    } else {
        Api::set_error_message(Messages::wrongLoginInfo());
        Api::redirect("/login.php");
    }
}

require_once "./components/header.php";
?>
<main class="vertical-center">
    <article style="width:400px">
        <header>
            <h1>Login</h1>
        </header>
        <form method="POST" action="/login.php">
            <fieldset>
                <input type="email" id="email" name="email" placeholder="Email" required>
            </fieldset>
            <fieldset>
                <input type="password" id="password" name="password" placeholder="Contraseña" required>
            </fieldset>
            <button class="primary float-right" style="width:40%" type="submit">Entrar</button>
        </form>
    </article>
</main>
<?php require_once "./components/footer.php"; ?>
