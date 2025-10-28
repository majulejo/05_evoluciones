<?php
date_default_timezone_set('Europe/Madrid');

require 'config_correo.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = htmlspecialchars($_POST['nombre']);
    $telefono = htmlspecialchars($_POST['telefono']);
    $usuario = htmlspecialchars($_POST['usuario']);
    $contrasena = htmlspecialchars($_POST['contrasena']);

    $mensaje = "
        <h2>Nueva Solicitud de Acceso</h2>
        <p><strong>Nombre:</strong> $nombre</p>
        <p><strong>Teléfono:</strong> $telefono</p>
        <p><strong>Usuario solicitado:</strong> $usuario</p>
        <p><strong>Contraseña deseada:</strong> $contrasena</p>
    ";

    $headers = "From: no-reply@evolucionuci.es\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";

    if (mail(EMAIL_DESTINO, EMAIL_ASUNTO, $mensaje, $headers)) {
        echo "<script>alert('Solicitud enviada correctamente. Serás contactado pronto.'); window.location.href='login.html';</script>";
    } else {
        echo "<script>alert('Hubo un error al enviar tu solicitud. Inténtalo más tarde.'); window.location.href='register.html';</script>";
    }
} else {
    header("Location: register.html");
}
?>