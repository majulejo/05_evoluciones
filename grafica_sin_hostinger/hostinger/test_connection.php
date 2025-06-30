<?php
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=u724879249_grafica_uci",
        "u724879249_grafica_user",
        "Periquit0.1"
    );
    echo "✅ Conexión exitosa!";
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>