<?php
// public/guardar_cita.php

// 1. CONFIGURACIÓN DE BASE DE DATOS (PLESK)
$host = "localhost";
$dbname = "citas_sadasi";      
$username = "admin_citas";     
$password = "Sadasi123";   

// 2. CONFIGURACIÓN DEL CORREO (A QUIÉN LE LLEGA)
$correo_destino = "saulcalderon@ollintem.com.mx"; // <--- CAMBIA ESTO POR EL CORREO REAL DE KAREN O EL TUYO
$asunto_correo = "🔔 Nueva Cita Agendada - Web Sadasi";

// Permisos para Astro
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

$json = file_get_contents("php://input");
$data = json_decode($json);

if (isset($data->nombre) && isset($data->fecha)) {
    try {
        // A) GUARDAR EN BASE DE DATOS (Lo que ya teníamos)
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "INSERT INTO citas (nombre, telefono, tipo_credito, modelo_interes, fecha, hora) 
                VALUES (:nombre, :telefono, :tipo, :modelo, :fecha, :hora)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nombre', $data->nombre);
        $stmt->bindParam(':telefono', $data->telefono);
        $stmt->bindParam(':tipo', $data->tipoCredito);
        $stmt->bindParam(':modelo', $data->modeloInteres);
        $stmt->bindParam(':fecha', $data->fecha);
        $stmt->bindParam(':hora', $data->hora);

        if($stmt->execute()) {
            
            // B) PREPARAR EL CORREO
            $mensaje = "
            Hola, tienes una nueva cita registrada:
            
            👤 Cliente: " . $data->nombre . "
            📞 Teléfono: " . $data->telefono . "
            📅 Fecha: " . $data->fecha . "
            ⏰ Hora: " . $data->hora . "
            🏠 Interés: " . $data->modeloInteres . "
            💳 Crédito: " . $data->tipoCredito . "
            ";

            // --- CORRECCIÓN IMPORTANTE AQUÍ ---
            // Usamos un correo que SÍ pertenezca a tu dominio real
            $domain_email = "no-reply@ollintem.com.mx"; 
            
            $headers = "From: Web Citas <" . $domain_email . ">" . "\r\n" .
                       "Reply-To: " . $domain_email . "\r\n" .
                       "X-Mailer: PHP/" . phpversion();

            // Intentar enviar y guardar el resultado en una variable
            $enviado = mail($correo_destino, $asunto_correo, $mensaje, $headers);

            if ($enviado) {
                echo json_encode(["status" => "success", "message" => "Guardado y Correo ENVIADO"]);
            } else {
                // Si entra aquí, es que Plesk bloqueó la salida
                echo json_encode(["status" => "success", "message" => "Guardado, pero FALLÓ el envío de correo (Revisar logs de Plesk)"]);
            }

        } else {
            echo json_encode(["status" => "error", "message" => "Fallo al guardar en BD"]);
        }

    } catch(PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Error BD: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Datos vacíos"]);
}
?>