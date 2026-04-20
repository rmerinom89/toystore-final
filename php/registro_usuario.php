<?php
session_start();
require_once "conexion.php";

$mensaje = "";
$tipoMensaje = "";

function filtrarDato($dato) {
    $dato = trim($dato);
    $dato = stripslashes($dato);
    $dato = htmlspecialchars($dato);
    return $dato;
}

function contrasenaSegura($password) {
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = filtrarDato($_POST["nombre"] ?? "");
    $email = filtrarDato($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $direccion = filtrarDato($_POST["direccion"] ?? "");
    $telefono = filtrarDato($_POST["telefono"] ?? "");

    if ($nombre === "" || $email === "" || $password === "" || $direccion === "" || $telefono === "") {
        $mensaje = "Todos los campos son obligatorios.";
        $tipoMensaje = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "El correo no tiene un formato válido.";
        $tipoMensaje = "danger";
    } elseif (!contrasenaSegura($password)) {
        $mensaje = "La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número.";
        $tipoMensaje = "warning";
    } else {
        try {
            $sql = "INSERT INTO USUARIOS (nombre, email, contrasena, direccion, telefono)
                    VALUES (:nombre, :email, :contrasena, :direccion, :telefono)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ":nombre" => $nombre,
                ":email" => $email,
                ":contrasena" => password_hash($password, PASSWORD_DEFAULT),
                ":direccion" => $direccion,
                ":telefono" => $telefono
            ]);

            $mensaje = "Usuario registrado correctamente.";
            $tipoMensaje = "success";
        } catch (PDOException $e) {
            $mensaje = "No fue posible registrar el usuario. Verifica que el correo no esté repetido.";
            $tipoMensaje = "danger";
        }
    }
}

$usuarios = $pdo->query("SELECT id, nombre, email, direccion, telefono FROM USUARIOS ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <header class="bg-primary text-white py-4 shadow-sm">
        <div class="container">
            <h1 class="mb-1">Registro de usuarios</h1>
            <p class="mb-0">ToyStore Online</p>
        </div>
    </header>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">ToyStore</a>
            <div class="ms-auto">
                <a class="btn btn-outline-light btn-sm" href="../index.php">Volver al inicio</a>
            </div>
        </div>
    </nav>

    <main class="container my-5">
        <?php if ($mensaje !== ""): ?>
            <div class="alert alert-<?php echo $tipoMensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h4 mb-4">Nuevo usuario</h2>

                        <form method="post" action="">
                            <div class="mb-3">
                                <label class="form-label">Nombre</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Correo</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Contraseña</label>
                                <input type="password" name="password" class="form-control" required>
                                <div class="form-text">Debe tener 8 caracteres, mayúscula, minúscula y número.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Dirección</label>
                                <input type="text" name="direccion" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="telefono" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Registrar usuario</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h4 mb-4">Usuarios registrados</h2>

                        <?php if (count($usuarios) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Email</th>
                                            <th>Dirección</th>
                                            <th>Teléfono</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usuarios as $usuario): ?>
                                            <tr>
                                                <td><?php echo $usuario["id"]; ?></td>
                                                <td><?php echo htmlspecialchars($usuario["nombre"]); ?></td>
                                                <td><?php echo htmlspecialchars($usuario["email"]); ?></td>
                                                <td><?php echo htmlspecialchars($usuario["direccion"]); ?></td>
                                                <td><?php echo htmlspecialchars($usuario["telefono"]); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-secondary mb-0">No hay usuarios registrados todavía.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>