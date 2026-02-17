<!DOCTYPE html>
<html lang="es">
<head>
  <title>GESTI&Oacute;N DE INVENTARIOS Y DESPACHOS</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#17486b">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <link rel="icon" type="image/svg+xml" href="imagenes/favicon_gestion.svg">
  <link rel="manifest" href="manifest.webmanifest">
  <link rel="apple-touch-icon" href="imagenes/pwa-icon-192.png">

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/js-cookie/2.2.1/js.cookie.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

  <link rel="stylesheet" href="css/index.css?v=20260217_03">
  <script src="js/index.js?v=20260217_03"></script>
</head>
<body class="login-body">
  <div class="login-glow login-glow-a"></div>
  <div class="login-glow login-glow-b"></div>

  <main class="container login-shell">
    <form class="form-signin login-card">
      <div class="login-brand">
        <div class="login-brand-icon">
          <i class="fas fa-boxes"></i>
        </div>
        <h1>GESTI&Oacute;N DE INVENTARIOS Y DESPACHOS</h1>
        <p id="frase-productividad">Organiza tu dia, acelera tus resultados.</p>
      </div>

      <div class="form-floating">
        <label for="usuario">Usuario</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fas fa-user"></i></span>
          <input type="text" class="form-control" id="usuario" placeholder="Usuario" onkeyup="this.value = this.value.toUpperCase()">
        </div>
      </div>

      <div class="form-floating">
        <label for="password">Contrasena</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fas fa-lock"></i></span>
          <input type="password" class="form-control" id="password" placeholder="Contrasena">
          <span class="ver-contrasena" onclick="verContrasena()">
            <i class="fas fa-eye" id="ojo"></i>
          </span>
        </div>
      </div>

      <div class="form-check">
        <input type="checkbox" class="form-check-input" id="recordar-credenciales">
        <label class="form-check-label" for="recordar-credenciales">Recordar credenciales</label>
      </div>

      <button type="button" class="btn btn-primary w-100 py-2" id="ingresar">
        <i class="fas fa-sign-in-alt"></i> Ingresar
      </button>
      <button type="button" class="btn btn-outline-primary w-100 py-2 mt-2 d-none" id="instalar-app">
        <i class="fas fa-download"></i> Instalar app
      </button>
    </form>
  </main>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
</body>
</html>
