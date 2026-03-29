<?php
session_start();
include "connection.php";

$email = $_POST['email'];
$password = $_POST['password'];

// Buscar usuario
$sql = "SELECT * FROM user_credentials WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    // Verificar contraseña (si no usas hash, usa comparación directa)
    if ($password == $user['password']) {

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['usertype'] = $user['usertype'];

        header("Location: dashboard.php");
        exit();

    } else {
        echo "❌ Contraseña incorrecta";
    }

} else {
    echo "❌ Usuario no encontrado";
}
?>