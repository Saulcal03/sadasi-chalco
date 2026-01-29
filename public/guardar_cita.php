<?php
// public/guardar_cita.php

// 1. CARGAR PHPMAILER (La librería que descargaste)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'mail/Exception.php';
require 'mail/PHPMailer.php';
require 'mail/SMTP.php';

// 2. DATOS DE CONEXIÓN
$host = "localhost";
$dbname = "citas_sadasi";
$username = "admin_citas"; 
$password_bd = "Sadasi123"; // Tu contraseña de BD

// DATOS DE TU CORREO (SMTP)
$smtp_host = 'mail.ollintem.com.mx'; // Generalmente es mail.tudominio.com
$smtp_user = 'karenprueba@ollintem.com.mx';
$smtp_pass = '034*qwgY6'; // <--- PON LA CONTRASEÑA QUE LE PUSISTE AL CORREO EN PLESK
$smtp_port = 587; // Puerto estándar

// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

$json = file_get_contents("php://input");
$data = json_decode($json);

if (isset($data->nombre) && isset($data->fecha)) {
    try {
        // --- PASO A: GUARDAR EN BD ---
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password_bd);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Verificar si ya está ocupado antes de guardar (Doble seguridad)
        $check = $conn->prepare("SELECT id FROM citas WHERE fecha = :f AND hora = :h");
        $check->execute([':f' => $data->fecha, ':h' => $data->hora]);
        
        if($check->rowCount() > 0) {
            echo json_encode(["status" => "error", "message" => "Ese horario acaba de ser ganado por otra persona."]);
            exit;
        }

        $sql = "INSERT INTO citas (nombre, correo, telefono, tipo_credito, modelo_interes, fecha, hora) 
                VALUES (:nombre, :correo, :telefono, :tipo, :modelo, :fecha, :hora)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nombre', $data->nombre);
        $stmt->bindParam(':correo', $data->correo); // <--- AHORA RECIBIMOS EL CORREO DEL CLIENTE
        $stmt->bindParam(':telefono', $data->telefono);
        $stmt->bindParam(':tipo', $data->tipoCredito);
        $stmt->bindParam(':modelo', $data->modeloInteres);
        $stmt->bindParam(':fecha', $data->fecha);
        $stmt->bindParam(':hora', $data->hora);

        if($stmt->execute()) {
            
            // --- PASO B: ENVIAR CORREOS CON SMTP ---
            $mail = new PHPMailer(true);

            try {
                // Configuración del Servidor
                $mail->isSMTP();
                $mail->Host       = $smtp_host;
                $mail->SMTPAuth   = true;
                $mail->Username   = $smtp_user;
                $mail->Password   = $smtp_pass;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
                $mail->Port       = $smtp_port;
                $mail->CharSet    = 'UTF-8';

                // 1. CORREO PARA EL ADMIN (TÚ)
                $mail->setFrom($smtp_user, 'Sistema Citas Sadasi');
                $mail->addAddress('crchatgp@gmail.com'); // TU CORREO PERSONAL
                
                $mail->isHTML(true);
                $mail->Subject = '🔔 Nueva Cita Agendada';
                $mail->Body    = "<h1>Nueva Reserva</h1>
                                  <p><strong>Cliente:</strong> {$data->nombre}</p>
                                  <p><strong>Fecha:</strong> {$data->fecha} a las {$data->hora}</p>
                                  <p><strong>Teléfono:</strong> {$data->telefono}</p>";
                $mail->send();

                // 2. CORREO PARA EL CLIENTE
                $mail->clearAddresses(); // Borrar destinatario anterior
                $mail->addAddress($data->correo); // Correo del cliente
                
                $mail->Subject = '✅ Confirmación de Cita - Sadasi Chalco';
                $mail->Body    = "<h1>¡Hola {$data->nombre}!</h1>
                                  <p>Tu cita ha sido confirmada correctamente.</p>
                                  <p>Te esperamos el día <strong>{$data->fecha}</strong> a las <strong>{$data->hora}</strong>.</p>
                                  <p>Atte: Karen Martínez.</p>";
                $mail->send();

                echo json_encode(["status" => "success", "message" => "Guardado y correos enviados"]);

            } catch (Exception $e) {
                echo json_encode(["status" => "success", "message" => "Guardado, pero error de correo: {$mail->ErrorInfo}"]);
            }

        } else {
            echo json_encode(["status" => "error", "message" => "Fallo al guardar en BD"]);
        }

    } catch(PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Error BD: " . $e->getMessage()]);
    }
}
?>