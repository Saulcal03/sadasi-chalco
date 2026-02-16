<?php
// public/guardar_cita.php

// 1. CARGAR PHPMAILER
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Asegúrate de que estas rutas sean correctas en tu servidor
require 'mail/Exception.php';
require 'mail/PHPMailer.php';
require 'mail/SMTP.php';

// 2. DATOS DE CONEXIÓN A BASE DE DATOS
$host = "localhost";
$dbname = "citas_sadasi";
$username = "admin_citas"; 
$password_bd = "Sadasi123"; 

// --- 3. CONFIGURACIÓN DEL NUEVO CORREO OFICIAL (SMTP) ---
// Esto es lo que evita el SPAM: Usar el dominio real autenticado
$smtp_host = 'mail.karensadasi.com.mx'; // Servidor de correo de tu dominio
$smtp_user = 'contacto@karensadasi.com.mx'; // Tu nuevo correo creado en Plesk
$smtp_pass = 'Karensa123.'; // La contraseña que pusiste en la captura
$smtp_port = 587; // Puerto seguro estándar

// Headers para recibir JSON desde el frontend
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// Leer datos del formulario
$json = file_get_contents("php://input");
$data = json_decode($json);

if (isset($data->nombre) && isset($data->fecha)) {
    try {
        // --- PASO A: GUARDAR EN BASE DE DATOS ---
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password_bd);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Verificar disponibilidad
        $check = $conn->prepare("SELECT id FROM citas WHERE fecha = :f AND hora = :h");
        $check->execute([':f' => $data->fecha, ':h' => $data->hora]);
        
        if($check->rowCount() > 0) {
            echo json_encode(["status" => "error", "message" => "Ese horario ya fue ocupado."]);
            exit;
        }

        $sql = "INSERT INTO citas (nombre, correo, telefono, tipo_credito, modelo_interes, fecha, hora) 
                VALUES (:nombre, :correo, :telefono, :tipo, :modelo, :fecha, :hora)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nombre', $data->nombre);
        $stmt->bindParam(':correo', $data->correo);
        $stmt->bindParam(':telefono', $data->telefono);
        $stmt->bindParam(':tipo', $data->tipoCredito);
        $stmt->bindParam(':modelo', $data->modeloInteres);
        $stmt->bindParam(':fecha', $data->fecha);
        $stmt->bindParam(':hora', $data->hora);

        if($stmt->execute()) {
            
            // --- PASO B: ENVIAR CORREOS CON LA CUENTA OFICIAL ---
            $mail = new PHPMailer(true);

            try {
                // Configuración del Servidor SMTP
                $mail->isSMTP();
                $mail->Host       = $smtp_host;
                $mail->SMTPAuth   = true;
                $mail->Username   = $smtp_user;
                $mail->Password   = $smtp_pass;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
                $mail->Port       = $smtp_port;
                $mail->CharSet    = 'UTF-8';

                // --- CORREO 1: AVISO PARA TI (ADMIN) ---
                $mail->setFrom($smtp_user, 'Karen Sadasi Citas'); // Remitente oficial
                $mail->addAddress('crchatgp@gmail.com'); // Tu correo personal donde te llegan los avisos
                // Opcional: Si quieres que también llegue copia al correo corporativo, descomenta la siguiente línea:
                // $mail->addCC('contacto@karensadasi.com.mx'); 
                
                $mail->isHTML(true);
                $mail->Subject = '🔔 Nueva Cita Agendada - Web';
                $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; color: #333;'>
                        <h2 style='color: #293F71;'>Nueva Cita Recibida</h2>
                        <p><strong>Cliente:</strong> {$data->nombre}</p>
                        <p><strong>Teléfono:</strong> <a href='tel:{$data->telefono}'>{$data->telefono}</a></p>
                        <p><strong>Correo:</strong> {$data->correo}</p>
                        <hr>
                        <p><strong>Fecha:</strong> {$data->fecha}</p>
                        <p><strong>Hora:</strong> {$data->hora}</p>
                        <p><strong>Interés:</strong> {$data->modeloInteres} ({$data->tipoCredito})</p>
                    </div>
                ";
                $mail->send();

                // --- CORREO 2: CONFIRMACIÓN PARA EL CLIENTE ---
                $mail->clearAddresses(); // Limpiamos destinatarios anteriores
                $mail->clearCCs();
                $mail->addAddress($data->correo); // Correo del cliente que llenó el formulario
                
                $mail->Subject = '✅ Cita Confirmada - Karen Martínez';
                $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; text-align: center; color: #333;'>
                        <h1 style='color: #E76627;'>¡Gracias {$data->nombre}!</h1>
                        <p style='font-size: 16px;'>Hemos recibido tu solicitud correctamente.</p>
                        <div style='background-color: #f4f4f4; padding: 15px; margin: 20px 0; border-radius: 8px;'>
                            <p><strong>Fecha:</strong> {$data->fecha}</p>
                            <p><strong>Hora:</strong> {$data->hora}</p>
                        </div>
                        <p>En breve me pondré en contacto contigo para confirmar los detalles.</p>
                        <br>
                        <p style='color: #888; font-size: 12px;'>Atte: Karen Martínez | Asesora Inmobiliaria Sadasi</p>
                    </div>
                ";
                $mail->send();

                echo json_encode(["status" => "success", "message" => "Cita guardada y correos enviados"]);

            } catch (Exception $e) {
                // Si falla el correo pero se guardó en BD, avisamos el error técnico
                echo json_encode(["status" => "success", "message" => "Cita guardada, pero hubo error enviando correo: {$mail->ErrorInfo}"]);
            }

        } else {
            echo json_encode(["status" => "error", "message" => "Fallo al guardar en base de datos"]);
        }

    } catch(PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Error de conexión BD: " . $e->getMessage()]);
    }
}
?>