<?php
session_start();
require_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php");
    exit;
}

$email = trim($_POST["email"] ?? "");
$password = trim($_POST["password"] ?? "");

if ($email === "" || $password === "") {
    header("Location: ../index.php?error=1");
    exit;
}

$sql = "SELECT * FROM USUARIOS WHERE email = :email";
$stmt = $pdo->prepare($sql);
$stmt->execute([":email" => $email]);
$usuario = $stmt->fetch();

if ($usuario && password_verify($password, $usuario["contrasena"])) {
    session_regenerate_id(true);
    $_SESSION["id_usuario"] = $usuario["id"];
    $_SESSION["nombre_usuario"] = $usuario["nombre"];
    $_SESSION["email_usuario"] = $usuario["email"];

    if (!isset($_SESSION["carrito"])) {
        $_SESSION["carrito"] = [];
    }

    header("Location: carrito.php");
    exit;
}

header("Location: ../index.php?error=1");
exit;