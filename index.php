<?php
session_start();
require_once __DIR__ . '/php/conexion.php';

$usuarioActivo = isset($_SESSION["id_usuario"]);
$nombreUsuario = $_SESSION["nombre_usuario"] ?? "";

$mensaje = "";
$tipoMensaje = "";

if (isset($_GET["error"])) {
    $mensaje = "Correo o contraseña incorrectos.";
    $tipoMensaje = "danger";
}

if (isset($_GET["logout"])) {
    $mensaje = "Sesión cerrada correctamente.";
    $tipoMensaje = "success";
}

if (isset($_GET["login"])) {
    $mensaje = "Inicio de sesión correcto.";
    $tipoMensaje = "success";
}

$juguetesDestacados = [];
try {
    $stmt = $pdo->query("
        SELECT id, nombre, descripcion, edad, precio, cantidad_inventario
        FROM JUGUETES
        ORDER BY id DESC
        LIMIT 3
    ");
    $juguetesDestacados = $stmt->fetchAll();
} catch (Exception $e) {
    $juguetesDestacados = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ToyStore Online</title>
  <link rel="preconnect" href="https://cdn.jsdelivr.net">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/styles.css">
</head>
<body>
  <header class="bg-primary text-white py-4 shadow-sm">
    <div class="container">
      <h1 class="mb-1">ToyStore Online</h1>
      <p class="mb-0">Tienda de juguetes para niños de todas las edades</p>
    </div>
  </header>

  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="index.php">ToyStore</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="menuNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link active" href="#inicio">Inicio</a></li>
          <li class="nav-item"><a class="nav-link" href="#novedades">Novedades</a></li>
          <li class="nav-item"><a class="nav-link" href="php/registro_usuario.php">Registro usuarios</a></li>
          <li class="nav-item"><a class="nav-link" href="php/registro_juguete.php">Registro juguetes</a></li>
          <?php if ($usuarioActivo): ?>
            <li class="nav-item"><a class="nav-link" href="php/carrito.php">Carrito</a></li>
            <li class="nav-item"><a class="nav-link" href="php/logout.php">Cerrar sesión</a></li>
          <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="#login">Iniciar sesión</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

  <main class="container my-5">
    <?php if ($mensaje !== ""): ?>
      <div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($mensaje); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <section id="inicio" class="hero-box text-center mb-5">
      <h2 class="mb-3">Bienvenido a nuestra tienda de juguetes</h2>
      <p class="lead">
        Aquí podrás registrarte, iniciar sesión, revisar novedades disponibles y gestionar tu carrito de compra de forma segura.
      </p>

      <?php if ($usuarioActivo): ?>
        <p class="mb-4">Hola, <strong><?php echo htmlspecialchars($nombreUsuario); ?></strong>. Ya puedes continuar con tu compra.</p>
        <a href="php/carrito.php" class="btn btn-success btn-lg">Ir al carrito</a>
      <?php else: ?>
        <a href="#login" class="btn btn-primary btn-lg">Comenzar</a>
      <?php endif; ?>
    </section>

    <section id="novedades" class="mb-5">
      <h3 class="mb-4">Novedades disponibles</h3>
      <div class="row g-4">
        <?php if (count($juguetesDestacados) > 0): ?>
          <?php foreach ($juguetesDestacados as $juguete): ?>
            <div class="col-md-4">
              <div class="card h-100 shadow-sm">
                <div class="card-body">
                  <span class="badge bg-info text-dark mb-2">Edad: <?php echo htmlspecialchars($juguete["edad"]); ?></span>
                  <h5 class="card-title"><?php echo htmlspecialchars($juguete["nombre"]); ?></h5>
                  <p class="card-text"><?php echo htmlspecialchars($juguete["descripcion"]); ?></p>
                  <p class="mb-1"><strong>Precio:</strong> $<?php echo number_format((float)$juguete["precio"], 0, ",", "."); ?></p>
                  <p class="mb-3"><strong>Stock:</strong> <?php echo (int)$juguete["cantidad_inventario"]; ?></p>
                  <?php if ($usuarioActivo): ?>
                    <a href="php/carrito.php" class="btn btn-success">Comprar</a>
                  <?php else: ?>
                    <a href="#login" class="btn btn-outline-primary">Inicia sesión para comprar</a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="col-12">
            <div class="alert alert-secondary mb-0">
              Aún no hay juguetes registrados. Puedes agregarlos desde la sección de registro de juguetes.
            </div>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <?php if (!$usuarioActivo): ?>
      <section id="login" class="row justify-content-center">
        <div class="col-md-6">
          <div class="card shadow">
            <div class="card-body">
              <h3 class="card-title mb-4">Inicio de sesión</h3>

              <form id="loginForm" action="php/login.php" method="post" novalidate>
                <div class="mb-3">
                  <label for="email" class="form-label">Correo electrónico</label>
                  <input type="email" class="form-control" id="email" name="email" required>
                  <div id="emailFeedback" class="form-text text-danger"></div>
                </div>

                <div class="mb-3">
                  <label for="password" class="form-label">Contraseña</label>
                  <input type="password" class="form-control" id="password" name="password" required>
                  <div id="passwordFeedback" class="form-text text-danger"></div>
                </div>

                <button type="submit" class="btn btn-primary w-100">Ingresar</button>
              </form>
            </div>
          </div>
        </div>
      </section>
    <?php endif; ?>
  </main>

  <footer class="bg-dark text-white text-center py-3 mt-5">
    <p class="mb-0">© 2026 ToyStore Online - Proyecto Final</p>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/login.js"></script>
</body>
</html>