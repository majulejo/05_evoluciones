<?php
// Después de insertar el nuevo usuario
$newUserId = $conn->lastInsertId();

// Eliminar cualquier draft existente para este nuevo usuario
$stmt = $pdo->prepare("DELETE FROM drafts WHERE user_id = ?");
$stmt->execute([$newUserId]);

echo json_encode(["success" => true, "message" => "Usuario creado correctamente."]);
?>