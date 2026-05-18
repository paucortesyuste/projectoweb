<?php
session_start();
include("conexion.php");

$mensaje = "";
$tipo = "";

// CONFIGURACIÓN DE CLOUDFLARE TURNSTILE
// Puedes usar estas dos claves de prueba universales de Cloudflare. 
// Funcionan SIEMPRE, en cualquier IP o dominio, sin registrarte en su web:
define('TURNSTILE_SITE_KEY', '1x00000000000000000000AA'); 
define('TURNSTILE_SECRET_KEY', '1x0000000000000000000000000000000AA');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre']);
    $empresa = trim($_POST['empresa']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Turnstile envía la respuesta en el parámetro 'cf-turnstile-response'
    $turnstile_response = isset($_POST['cf-turnstile-response']) ? $_POST['cf-turnstile-response'] : '';

    if (empty($nombre) || empty($empresa) || empty($email) || empty($password)) {
        $mensaje = "Todos los campos son obligatorios.";
        $tipo = "error";
    } elseif (empty($turnstile_response)) {
        $mensaje = "Por favor, completa la verificación de seguridad.";
        $tipo = "error";
    } else {
        // VALIDACIÓN DEL TOKEN EN EL BACKEND CONTRA LA API DE CLOUDFLARE
        $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        $datos = [
            'secret'   => TURNSTILE_SECRET_KEY,
            'response' => $turnstile_response,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ];

        $opciones = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($datos)
            ]
        ];

        $contexto  = stream_context_create($opciones);
        $resultado = file_get_contents($url, false, $contexto);
        $respuesta_json = json_decode($resultado, true);

        // Si Cloudflare rechaza el token o dice que es falso
        if (!$respuesta_json || !isset($respuesta_json['success']) || !$respuesta_json['success']) {
            $mensaje = "La verificación de seguridad ha fallado. Inténtalo de nuevo.";
            $tipo = "error";
        } else {
            // EL CAPTCHA ES VÁLIDO -> PROCEDEMOS CON EL REGISTRO SEGURO
            $consulta = pg_query_params($conn, "SELECT id FROM usuarios WHERE email = $1", array($email));

            if ($consulta && pg_num_rows($consulta) > 0) {
                $mensaje = "Ese correo ya está registrado.";
                $tipo = "error";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                $insert = pg_query_params(
                    $conn,
                    "INSERT INTO usuarios (nombre, empresa, email, password) VALUES ($1, $2, $3, $4)",
                    array($nombre, $empresa, $email, $password_hash)
                );

                if ($insert) {
                    $mensaje = "Registro completado correctamente. Ya puedes iniciar sesión.";
                    $tipo = "success";
                } else {
                    $mensaje = "Error al registrar el usuario.";
                    $tipo = "error";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro | CyberGuard Solutions</title>
  <link rel="stylesheet" href="style.css">
  <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>

<nav class="navbar">
  <div class="logo">CyberGuard Solutions</div>
  <div class="nav-links">
    <a href="index.php">Inicio</a>
    <a href="login.php">Login</a>
  </div>
</nav>

<div class="form-wrapper">
  <div class="glass-card form-card">
    <h2>Crear cuenta</h2>
    <p>Registra tu empresa y accede al portal seguro.</p>

    <?php if (!empty($mensaje)): ?>
      <div class="alert <?php echo $tipo === 'success' ? 'alert-success' : 'alert-error'; ?>">
        <?php echo htmlspecialchars($mensaje); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label>Nombre</label>
        <input type="text" name="nombre" class="form-control" required>
      </div>

      <div class="form-group">
        <label>Empresa</label>
        <input type="text" name="empresa" class="form-control" required>
      </div>

      <div class="form-group">
        <label>Correo electrónico</label>
        <input type="email" name="email" class="form-control" required>
      </div>

      <div class="form-group">
        <label>Contraseña</label>
        <input type="password" name="password" class="form-control" required>
      </div>

      <div class="form-group" style="display: flex; justify-content: center; margin-bottom: 20px;">
        <div class="cf-turnstile" data-sitekey="<?php echo TURNSTILE_SITE_KEY; ?>"></div>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;">Registrarse</button>
    </form>

    <div class="form-footer">
      ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
    </div>
  </div>
</div>

</body>
</html>
