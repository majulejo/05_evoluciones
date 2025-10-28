<?php
session_start();

// Token secreto para saltarte la página de mantenimiento
$token_acceso = "prueba123"; // Puedes cambiarlo por algo más seguro

// Si se pasa el token correcto, permitimos el acceso
if (isset($_GET['key']) && $_GET['key'] === $token_acceso) {
    // Saltar mantenimiento y acceder a app.php
    header("Location: app.php");
    exit();
}

// Si el usuario no está autenticado, volver a index.html
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Sistema en Mantenimiento</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(to right, #f8f9fa, #e9ecef);
      color: #333;
      text-align: center;
      padding: 100px 20px;
      margin: 0;
    }

    .container {
      max-width: 600px;
      margin: auto;
      background-color: white;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    h1 {
      font-size: 2.5em;
      color: #d9534f;
      margin-bottom: 20px;
    }

    p {
      font-size: 1.2em;
      margin-bottom: 30px;
    }

    img {
      width: 120px;
      height: auto;
      margin-bottom: 20px;
      animation: bounce 2s infinite;
    }

    @keyframes bounce {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }
  </style>
</head>
<body>

<div class="container">
  <img src="https://cdn-icons-png.flaticon.com/512/1163/1163661.png"  alt="En mantenimiento">
  <h1>Estamos realizando ajustes</h1>
  <p>Lo sentimos, estamos trabajando en mejorar tu experiencia.<br>La aplicación estará disponible muy pronto.</p>
</div>

</body>
</html>