<!DOCTYPE html>
<html>
<head>
  <title>Inventarios</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
  
  <!-- Toastr CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
  
  <!-- js-cookie -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/js-cookie/2.2.1/js.cookie.min.js"></script>
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
  
  <!-- Custom CSS -->
  <link rel="stylesheet" href="css/index.css">
  
  <!-- Custom JS -->
  <script src="js/index.js"></script>
</head>
<body>
  <div class="container">
    <!-- Formulario de inicio de sesión -->
    <form class="form-signin">
      <center>
        <!-- Logo de la aplicación -->
        <img class="mb-4" src="imagenes/inventario.jpg" alt="Logo Inventarios" width="300">
      </center>
      
      <!-- Campo de usuario -->
      <div class="form-floating">
        <input type="text" class="form-control" id="usuario" placeholder="Usuario" onkeyup="this.value = this.value.toUpperCase()">
        <label for="usuario">Usuario</label>
      </div>
      
      <!-- Campo de contraseña -->
      <div class="form-floating">
        <input type="password" class="form-control" id="password" placeholder="Contraseña">
        <label for="password">Contraseña</label>
        <span class="ver-contraseña" onclick="verContraseña()">
          <i class="fas fa-eye" id="ojo"></i>
        </span>
      </div>
      
      <!-- Checkbox para recordar credenciales -->
      <div class="form-check">
        <input type="checkbox" class="form-check-input" id="recordar-credenciales">
        <label class="form-check-label" for="recordar-credenciales">Recordar credenciales</label>
      </div>
      
      <!-- Botón de ingreso -->
      <button type="button" class="btn btn-primary w-100 py-2" id="ingresar">Ingresar</button>
    </form>
  </div>
  
  <!-- Toastr JS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
</body>
</html>