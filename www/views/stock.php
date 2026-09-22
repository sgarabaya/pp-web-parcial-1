<?php
require_once "../autoload.php";

Auth::requireRole("STOCK");

require_once "../components/header.php";
require_once "../components/navbar.php";
navbar("STOCK");
?>
<main>
    <article class="full-width">

    </article>
</main>

<?php require_once "../components/footer.php"; ?>
