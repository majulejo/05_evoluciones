<?php
header("Content-Type: text/plain");

$headers = getallheaders();

// Guarda en archivo para inspección si hace falta
file_put_contents("debug_headers.log", print_r($headers, true));

// Muestra directamente
echo "🔍 Cabeceras recibidas por el servidor:\n\n";
print_r($headers);
