<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../index.php");
    exit;
}

$idUsuario = $_SESSION["id_usuario"];
$nombreUsuario = $_SESSION["nombre_usuario"] ?? "Usuario";

function sincronizarCarritoSesion(PDO $pdo, int $idUsuario): array
{
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.id_producto,
            c.cantidad,
            c.monto_total,
            j.nombre,
            j.precio,
            j.edad,
            j.cantidad_inventario
        FROM CARRITO c
        INNER JOIN JUGUETES j ON c.id_producto = j.id
        WHERE c.id_usuario = :id_usuario
        ORDER BY c.id DESC
    ");
    $stmt->execute([":id_usuario" => $idUsuario]);
    $items = $stmt->fetchAll();

    $_SESSION["carrito"] = $items;
    return $items;
}

function redirigirConMensaje(string $tipo, string $texto): void
{
    $_SESSION["flash"] = [
        "tipo" => $tipo,
        "texto" => $texto
    ];
    header("Location: carrito.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "";

    if ($accion === "guardar") {
        $idProducto = (int)($_POST["id_producto"] ?? 0);
        $cantidad = (int)($_POST["cantidad"] ?? 0);

        if ($idProducto <= 0 || $cantidad <= 0) {
            redirigirConMensaje("danger", "Debes seleccionar un juguete y una cantidad válida.");
        }

        $stmtProducto = $pdo->prepare("SELECT id, nombre, precio, cantidad_inventario FROM JUGUETES WHERE id = :id");
        $stmtProducto->execute([":id" => $idProducto]);
        $producto = $stmtProducto->fetch();

        if (!$producto) {
            redirigirConMensaje("danger", "El juguete seleccionado no existe.");
        }

        if ($cantidad > (int)$producto["cantidad_inventario"]) {
            redirigirConMensaje("warning", "La cantidad solicitada supera el inventario disponible.");
        }

        $stmtExiste = $pdo->prepare("
            SELECT id, cantidad
            FROM CARRITO
            WHERE id_usuario = :id_usuario AND id_producto = :id_producto
        ");
        $stmtExiste->execute([
            ":id_usuario" => $idUsuario,
            ":id_producto" => $idProducto
        ]);
        $existente = $stmtExiste->fetch();

        if ($existente) {
            $nuevaCantidad = (int)$existente["cantidad"] + $cantidad;

            if ($nuevaCantidad > (int)$producto["cantidad_inventario"]) {
                redirigirConMensaje("warning", "No es posible agregar esa cantidad, supera el inventario disponible.");
            }

            $nuevoTotal = (float)$producto["precio"] * $nuevaCantidad;

            $stmt = $pdo->prepare("
                UPDATE CARRITO
                SET cantidad = :cantidad, monto_total = :monto_total
                WHERE id = :id
            ");
            $stmt->execute([
                ":cantidad" => $nuevaCantidad,
                ":monto_total" => $nuevoTotal,
                ":id" => $existente["id"]
            ]);
        } else {
            $montoTotal = (float)$producto["precio"] * $cantidad;

            $stmt = $pdo->prepare("
                INSERT INTO CARRITO (id_usuario, id_producto, cantidad, monto_total)
                VALUES (:id_usuario, :id_producto, :cantidad, :monto_total)
            ");
            $stmt->execute([
                ":id_usuario" => $idUsuario,
                ":id_producto" => $idProducto,
                ":cantidad" => $cantidad,
                ":monto_total" => $montoTotal
            ]);
        }

        sincronizarCarritoSesion($pdo, $idUsuario);
        redirigirConMensaje("success", "Producto agregado al carrito correctamente.");
    }

    if ($accion === "actualizar") {
        $idCarrito = (int)($_POST["id_carrito"] ?? 0);
        $cantidad = (int)($_POST["cantidad"] ?? 0);

        if ($idCarrito <= 0 || $cantidad <= 0) {
            redirigirConMensaje("danger", "La cantidad debe ser mayor a cero.");
        }

        $stmt = $pdo->prepare("
            SELECT c.id, j.precio, j.cantidad_inventario
            FROM CARRITO c
            INNER JOIN JUGUETES j ON c.id_producto = j.id
            WHERE c.id = :id AND c.id_usuario = :id_usuario
        ");
        $stmt->execute([
            ":id" => $idCarrito,
            ":id_usuario" => $idUsuario
        ]);
        $item = $stmt->fetch();

        if (!$item) {
            redirigirConMensaje("danger", "No se encontró el producto del carrito.");
        }

        if ($cantidad > (int)$item["cantidad_inventario"]) {
            redirigirConMensaje("warning", "La cantidad solicitada supera el inventario disponible.");
        }

        $nuevoTotal = (float)$item["precio"] * $cantidad;

        $stmt = $pdo->prepare("
            UPDATE CARRITO
            SET cantidad = :cantidad, monto_total = :monto_total
            WHERE id = :id AND id_usuario = :id_usuario
        ");
        $stmt->execute([
            ":cantidad" => $cantidad,
            ":monto_total" => $nuevoTotal,
            ":id" => $idCarrito,
            ":id_usuario" => $idUsuario
        ]);

        sincronizarCarritoSesion($pdo, $idUsuario);
        redirigirConMensaje("success", "Cantidad actualizada correctamente.");
    }

    if ($accion === "eliminar") {
        $idCarrito = (int)($_POST["id_carrito"] ?? 0);

        $stmt = $pdo->prepare("
            DELETE FROM CARRITO
            WHERE id = :id AND id_usuario = :id_usuario
        ");
        $stmt->execute([
            ":id" => $idCarrito,
            ":id_usuario" => $idUsuario
        ]);

        sincronizarCarritoSesion($pdo, $idUsuario);
        redirigirConMensaje("success", "Producto eliminado del carrito.");
    }

    if ($accion === "vaciar") {
        $stmt = $pdo->prepare("DELETE FROM CARRITO WHERE id_usuario = :id_usuario");
        $stmt->execute([":id_usuario" => $idUsuario]);

        $_SESSION["carrito"] = [];
        redirigirConMensaje("success", "El carrito fue vaciado.");
    }

    if ($accion === "finalizar") {
        try {
            $pdo->beginTransaction();

            $stmtItems = $pdo->prepare("
                SELECT c.id, c.id_producto, c.cantidad, j.precio, j.cantidad_inventario
                FROM CARRITO c
                INNER JOIN JUGUETES j ON c.id_producto = j.id
                WHERE c.id_usuario = :id_usuario
            ");
            $stmtItems->execute([":id_usuario" => $idUsuario]);
            $items = $stmtItems->fetchAll();

            if (count($items) === 0) {
                $pdo->rollBack();
                redirigirConMensaje("warning", "No hay productos en el carrito.");
            }

            foreach ($items as $item) {
                if ((int)$item["cantidad"] > (int)$item["cantidad_inventario"]) {
                    $pdo->rollBack();
                    redirigirConMensaje("danger", "Uno de los productos ya no tiene stock suficiente.");
                }
            }

            foreach ($items as $item) {
                $nuevoInventario = (int)$item["cantidad_inventario"] - (int)$item["cantidad"];
                $stmtUpdate = $pdo->prepare("
                    UPDATE JUGUETES
                    SET cantidad_inventario = :cantidad
                    WHERE id = :id
                ");
                $stmtUpdate->execute([
                    ":cantidad" => $nuevoInventario,
                    ":id" => $item["id_producto"]
                ]);
            }

            $stmtDelete = $pdo->prepare("DELETE FROM CARRITO WHERE id_usuario = :id_usuario");
            $stmtDelete->execute([":id_usuario" => $idUsuario]);

            $pdo->commit();
            $_SESSION["carrito"] = [];

            redirigirConMensaje("success", "Pedido realizado en línea correctamente.");
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            redirigirConMensaje("danger", "No fue posible finalizar el pedido.");
        }
    }
}

$juguetes = $pdo->query("
    SELECT id, nombre, descripcion, edad, precio, cantidad_inventario
    FROM JUGUETES
    ORDER BY nombre ASC
")->fetchAll();

$itemsCarrito = sincronizarCarritoSesion($pdo, $idUsuario);

$flash = $_SESSION["flash"] ?? null;
unset($_SESSION["flash"]);

$totalGeneral = 0;
foreach ($itemsCarrito as $item) {
    $totalGeneral += (float)$item["monto_total"];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de compras - ToyStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <header class="bg-primary text-white py-4 shadow-sm">
        <div class="container">
            <h1 class="mb-1">ToyStore Online</h1>
            <p class="mb-0">Carrito de compras</p>
        </div>
    </header>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">ToyStore</a>
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="text-white">Hola, <?php echo htmlspecialchars($nombreUsuario); ?></span>
                <a class="btn btn-outline-light btn-sm" href="logout.php">Cerrar sesión</a>
            </div>
        </div>
    </nav>

    <main class="container my-5">
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash["tipo"]); ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($flash["texto"]); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h4 mb-4">Agregar juguete al carrito</h2>

                        <form id="formCarrito" method="post" action="">
                            <input type="hidden" name="accion" value="guardar">

                            <div class="mb-3">
                                <label for="id_producto" class="form-label">Juguete</label>
                                <select name="id_producto" id="id_producto" class="form-select" required>
                                    <option value="">Seleccione</option>
                                    <?php foreach ($juguetes as $juguete): ?>
                                        <option value="<?php echo $juguete["id"]; ?>" data-precio="<?php echo $juguete["precio"]; ?>">
                                            <?php echo htmlspecialchars($juguete["nombre"]); ?>
                                            - $<?php echo number_format((float)$juguete["precio"], 0, ",", "."); ?>
                                            - Stock: <?php echo (int)$juguete["cantidad_inventario"]; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="cantidad" class="form-label">Cantidad</label>
                                <input type="number" name="cantidad" id="cantidad" class="form-control" min="1" value="1" required>
                            </div>

                            <div class="mb-3">
                                <label for="monto_total_vista" class="form-label">Monto total</label>
                                <input type="text" id="monto_total_vista" class="form-control" readonly value="$0">
                            </div>

                            <button type="submit" class="btn btn-success w-100">Guardar en carrito</button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm mt-4">
                    <div class="card-body">
                        <h3 class="h5 mb-3">Catálogo disponible</h3>

                        <?php if (count($juguetes) > 0): ?>
                            <div class="list-group">
                                <?php foreach ($juguetes as $juguete): ?>
                                    <div class="list-group-item">
                                        <strong><?php echo htmlspecialchars($juguete["nombre"]); ?></strong><br>
                                        <span><?php echo htmlspecialchars($juguete["descripcion"]); ?></span><br>
                                        <small>Edad: <?php echo htmlspecialchars($juguete["edad"]); ?></small><br>
                                        <small>Precio: $<?php echo number_format((float)$juguete["precio"], 0, ",", "."); ?></small><br>
                                        <small>Stock: <?php echo (int)$juguete["cantidad_inventario"]; ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-secondary mb-0">No hay juguetes registrados.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h4 mb-4">Mi carrito</h2>

                        <?php if (count($itemsCarrito) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Juguete</th>
                                            <th>Precio</th>
                                            <th>Cantidad</th>
                                            <th>Total</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($itemsCarrito as $item): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($item["nombre"]); ?></strong><br>
                                                    <small>Edad: <?php echo htmlspecialchars($item["edad"]); ?></small>
                                                </td>
                                                <td>$<?php echo number_format((float)$item["precio"], 0, ",", "."); ?></td>
                                                <td style="min-width: 150px;">
                                                    <form method="post" action="" class="d-flex gap-2">
                                                        <input type="hidden" name="accion" value="actualizar">
                                                        <input type="hidden" name="id_carrito" value="<?php echo $item["id"]; ?>">
                                                        <input type="number" name="cantidad" class="form-control form-control-sm" min="1" max="<?php echo (int)$item["cantidad_inventario"]; ?>" value="<?php echo (int)$item["cantidad"]; ?>" required>
                                                </td>
                                                <td>$<?php echo number_format((float)$item["monto_total"], 0, ",", "."); ?></td>
                                                <td class="d-flex flex-column gap-2">
                                                        <button type="submit" class="btn btn-sm btn-primary">Actualizar</button>
                                                    </form>

                                                    <form method="post" action="">
                                                        <input type="hidden" name="accion" value="eliminar">
                                                        <input type="hidden" name="id_carrito" value="<?php echo $item["id"]; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger w-100">Eliminar</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3" class="text-end">Monto total a pagar</th>
                                            <th colspan="2">$<?php echo number_format($totalGeneral, 0, ",", "."); ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="d-flex gap-2 flex-wrap mt-3">
                                <form method="post" action="">
                                    <input type="hidden" name="accion" value="vaciar">
                                    <button type="submit" class="btn btn-outline-danger">Vaciar carrito</button>
                                </form>

                                <form method="post" action="">
                                    <input type="hidden" name="accion" value="finalizar">
                                    <button type="submit" class="btn btn-success">Realizar pedido en línea</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-secondary mb-0">Tu carrito está vacío.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="../index.php" class="btn btn-outline-secondary">Volver al inicio</a>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-dark text-white text-center py-3 mt-5">
        <p class="mb-0">© 2026 ToyStore Online - Proyecto Final</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/carrito.js"></script>
</body>
</html>