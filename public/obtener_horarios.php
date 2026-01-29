<?php
// public/obtener_horarios.php
$host = "localhost";
$dbname = "citas_sadasi";
$username = "admin_citas";
$password = "Sadasi123"; // TU CONTRASEÑA

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$fecha = $_GET['fecha'] ?? '';

if ($fecha) {
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Buscar horas ocupadas en esa fecha
        $stmt = $conn->prepare("SELECT hora FROM citas WHERE fecha = :fecha");
        $stmt->bindParam(':fecha', $fecha);
        $stmt->execute();
        
        // Devolver lista de horas ocupadas (ej: ["10:00", "12:00"])
        $horas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode($horas);

    } catch(PDOException $e) {
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}
?>