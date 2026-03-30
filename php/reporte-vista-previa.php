<?php
session_start();

if (!isset($_SESSION['pdf_content'])) {
    header('Location: ../index.php');
    exit();
}

echo $_SESSION['pdf_content'];
unset($_SESSION['pdf_content']);
unset($_SESSION['pdf_filename']);
?>