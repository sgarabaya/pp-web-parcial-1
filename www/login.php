<?php
require_once "autoload.php";

if (!empty($_SESSION["user.id"])) {
    header("Location: index.php");
    exit();
}

$error_message = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = Api::safe_get($_POST, "email");
    $password = Api::safe_get($_POST, "password");

    if (!$email || !$password) {
        $error_message = Messages::wrongLoginInfo();
    } else {
        $userRepo = new UserRepository();
        $user = $userRepo->findByEmail($email);

        if ($user && Crypto::passwordVerify($password, $user->passwordHash)) {
            $_SESSION["user.id"] = $user->id;
            $_SESSION["user.role"] = $user->role;
            header("Location: /index.php");
            exit();
        } else {
            $error_message = Messages::wrongLoginInfo();
        }
    }
}

require_once "./components/header.php";
?>
<main class="vertical-center">
    <article style="width:400px">
        <header>
            <h1>Login</h1>
        </header>
        <?php if ($error_message): ?>
            <p class="vertical-center error-message">
                <span><?= $error_message ?></span>
                &nbsp;&nbsp;
                <img class="tiny" src="/public/alert.png"/>
            </p>
        <?php endif; ?>
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

<?php if ($error_message): ?>
<style>
@keyframes vanish{
  0%{
    opacity: 1;
  }
  50%{
    opacity: 0.5;
  }
  100%{
    display: none;
    opacity: 0;
    transform:  scaleY(25%) rotateX(90deg);
  }
}
.vanished {
    animation: vanish 500ms;
}
</style>
<script>
    setTimeout( ()=> {
      const msg = document.querySelectorAll('.error-message')[0];
      msg.classList.add('vanished');
      Promise.all(msg.getAnimations().map((animation) => animation.finished)).then(() => msg.remove());
    }, 2500);
</script>
<?php endif; ?>

<?php require_once "./components/footer.php"; ?>
