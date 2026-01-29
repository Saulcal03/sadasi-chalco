<?php
// public/obtener_todas_citas.php

// 1. CONFIGURACIÓN (Igual que los otros)
$host = "localhost";
$dbname = "citas_sadasi";
$username = "admin_citas";
$password_bd = "Sadasi123"; // Tu contraseña de BD

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// 2. SEGURIDAD SIMPLE
// Solo mostraremos los datos si quien pregunta trae la contraseña correcta
$clave_secreta_admin = "SoyElJefe2026"; // <--- PUEDES CAMBIAR ESTO

$clave_recibida = $_GET['clave'] ?? '';

if ($clave_recibida !== $clave_secreta_admin) {
    echo json_encode(["status" => "error", "message" => "Acceso Denegado: Clave incorrecta"]);
    exit;
}

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password_bd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 3. OBTENER TODO ORDENADO POR FECHA (De la más nueva a la vieja)
    $stmt = $conn->prepare("SELECT * FROM citas ORDER BY fecha DESC, hora ASC");
    $stmt->execute();
    
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["status" => "success", "data" => $resultados]);

} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error BD: " . $e->getMessage()]);
}
?>