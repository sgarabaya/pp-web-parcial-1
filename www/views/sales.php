<?php
require_once "../autoload.php";

Auth::requireRole("SALES");

require_once "../components/header.php";
require_once "../components/navbar.php";
navbar("SALES");
?>
<main>
    <article class="full-width">

    </article>
</main>

<?php require_once "../components/footer.php"; ?>
