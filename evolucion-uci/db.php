<?php
session_start();

function connect() {
    try {
        $pdo = new PDO(
            'mysql:host=localhost;dbname=u724879249_evolucion_uci;charset=utf8mb4',
            'u724879249_jamarquez06',
            'Farolill01.'
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error de conexión']);
        exit;
    }
}