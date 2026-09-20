<?php
session_start();
require_once "autoload.php";
$userRepo = new UserRepository();

if (!empty($_SESSION["user.id"])) {
    header("Location: index.php");
    exit();
}

$error_message = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";

    $user = $userRepo->findByEmail($email);
    if ($user && Crypto::passwordVerify($password, $user->passwordHash)) {
        $_SESSION["user.id"] = $user->id;
        header("Location: index.php");
        exit();
    } else {
        $error_message = "Datos incorrectos.";
    }
}

require_once "./components/header.php";
?>

<div class="wrapper">
    <div class="login-container">
        <article>
            <?php if ($error_message): ?>
                <p style="color: var(--error-color); font-size: 0.9rem; margin-bottom: 1rem;">
                    <?php echo $error_message; ?>
                </p>
            <?php endif; ?>

            <header><span class="login-title">Login</span></header>
            <form method="POST" action="login.php">
                <input type="email" id="email" name="email" placeholder="Email" required>
                <input type="password" id="password" name="password" placeholder="Contraseña" required>
                <button type="submit">Entrar</button>
            </form>
        </article>
    </div>
</div>

<?php require_once "./components/footer.php"; ?>
