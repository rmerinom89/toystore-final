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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = filtrarDato($_POST["nombre"] ?? "");
    $descripcion = filtrarDato($_POST["descripcion"] ?? "");
    $edad = filtrarDato($_POST["edad"] ?? "");
    $precio = trim($_POST["precio"] ?? "");
    $inventario = trim($_POST["inventario"] ?? "");

    if ($nombre === "" || $descripcion === "" || $edad === "" || $precio === "" || $inventario === "") {
        $mensaje = "Todos los campos son obligatorios.";
        $tipoMensaje = "danger";
    } elseif (!is_numeric($precio) || $precio <= 0) {
        $mensaje = "El precio debe ser mayor a cero.";
        $tipoMensaje = "danger";
    } elseif (!is_numeric($inventario) || $inventario < 0) {
        $mensaje = "El inventario debe ser cero o mayor.";
        $tipoMensaje = "danger";
    } else {
        try {
            $sql = "INSERT INTO JUGUETES (nombre, descripcion, edad, precio, cantidad_inventario)
                    VALUES (:nombre, :descripcion, :edad, :precio, :cantidad)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ":nombre" => $nombre,
                ":descripcion" => $descripcion,
                ":edad" => $edad,
                ":precio" => $precio,
                ":cantidad" => $inventario
            ]);

            $mensaje = "Juguete registrado correctamente.";
            $tipoMensaje = "success";
        } catch (PDOException $e) {
            $mensaje = "No fue posible registrar el juguete.";
            $tipoMensaje = "danger";
        }
    }
}

$juguetes = $pdo->query("SELECT * FROM JUGUETES ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de juguetes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <header class="bg-primary text-white py-4 shadow-sm">
        <div class="container">
            <h1 class="mb-1">Registro de juguetes</h1>
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
                        <h2 class="h4 mb-4">Nuevo juguete</h2>

                        <form method="post" action="">
                            <div class="mb-3">
                                <label class="form-label">Nombre</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea name="descripcion" class="form-control" rows="4" required></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Edad recomendada</label>
                                <input type="text" name="edad" class="form-control" placeholder="3 a 6 años" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Precio</label>
                                <input type="number" name="precio" class="form-control" min="1" step="0.01" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Cantidad en inventario</label>
                                <input type="number" name="inventario" class="form-control" min="0" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Registrar juguete</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h4 mb-4">Juguetes registrados</h2>

                        <?php if (count($juguetes) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Edad</th>
                                            <th>Precio</th>
                                            <th>Inventario</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($juguetes as $juguete): ?>
                                            <tr>
                                                <td><?php echo $juguete["id"]; ?></td>
                                                <td><?php echo htmlspecialchars($juguete["nombre"]); ?></td>
                                                <td><?php echo htmlspecialchars($juguete["edad"]); ?></td>
                                                <td>$<?php echo number_format((float)$juguete["precio"], 0, ",", "."); ?></td>
                                                <td><?php echo (int)$juguete["cantidad_inventario"]; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-secondary mb-0">No hay juguetes registrados todavía.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>