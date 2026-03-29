<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<h2>Bienvenido al sistema académico 🎓</h2>

<p>Tu ID: <?php echo $_SESSION['user_id']; ?></p>
<p>Tipo de usuario: <?php echo $_SESSION['usertype']; ?></p>

<a href="logout.php">Cerrar sesión</a>