<?php
require_once "autoload.php";

Auth::requireRole("ANY");

require_once "components/header.php";
require_once "components/navbar.php";
navbar("OVERVIEW");
?>
<main>
    <article class="full-width">
        <header>
            <h1>Resumen</h1>
        </header>
    </article>
</main>

<?php require_once "components/footer.php"; ?>
