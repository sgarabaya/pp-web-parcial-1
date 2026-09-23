<?php
$message = Api::safe_get($_SESSION, "message");
$message_type = Api::safe_get($_SESSION, "message_type");

//reseteamos el mensaje una vez que lo mostramos
$_SESSION["message"] = null;
$_SESSION["message_type"] = null;

if ($message) { ?>
<div id="toast-container"></div>
<script>
const d = document;
d.addEventListener('DOMContentLoaded', () => {
  const toast = d.createElement('div');
  toast.className = `toast <?= $message_type ?? "error" ?>`;
  toast.textContent = `<?= $message ?>`;

  const progress = d.createElement('div');
  progress.className = 'toast-progress';
  toast.appendChild(progress);

  d.getElementById('toast-container').appendChild(toast);

  setTimeout(() => {
      toast.classList.add('fade-out');
      toast.addEventListener('animationend', () => toast.remove());
  }, 3000);
});
</script>
<?php }
?>
<script src="https://unpkg.com/lucide@latest"></script>
<script> document.addEventListener('DOMContentLoaded', ()=> lucide.createIcons(lucide.Icons));</script>
</body>
</html>
