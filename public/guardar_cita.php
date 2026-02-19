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
                $mail->setFrom($smtp_user, 'Karén Martínez Citas'); 
                $mail->addAddress('kmartinezb@sadasi.com'); 
                
                $mail->isHTML(true);
                $mail->Subject = '🔔 Nueva Cita Agendada - Web';
                
                // Diseño mejorado para el Admin (Fácil de leer en celular)
                $mail->Body = "
                <div style='font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;'>
                    <div style='max-width: 500px; margin: 0 auto; background-color: #fff; padding: 30px; border-radius: 8px; border-top: 5px solid #293F71;'>
                        <h2 style='color: #293F71; margin-top: 0;'>🔔 Nueva Cita Agendada</h2>
                        <p style='color: #555;'>Tienes un nuevo prospecto desde la página web:</p>
                        <table width='100%' cellpadding='8' cellspacing='0' style='border-collapse: collapse; margin-top: 20px;'>
                            <tr style='background-color: #f9f9f9;'><td width='30%'><strong>Cliente:</strong></td><td>{$data->nombre}</td></tr>
                            <tr><td><strong>Teléfono:</strong></td><td><a href='tel:{$data->telefono}'>{$data->telefono}</a></td></tr>
                            <tr style='background-color: #f9f9f9;'><td><strong>Correo:</strong></td><td>{$data->correo}</td></tr>
                            <tr><td><strong>Fecha:</strong></td><td><strong style='color:#E76627;'>{$data->fecha}</strong></td></tr>
                            <tr style='background-color: #f9f9f9;'><td><strong>Hora:</strong></td><td><strong style='color:#E76627;'>{$data->hora}</strong></td></tr>
                            <tr><td><strong>Modelo:</strong></td><td>{$data->modeloInteres}</td></tr>
                            <tr style='background-color: #f9f9f9;'><td><strong>Crédito:</strong></td><td>{$data->tipoCredito}</td></tr>
                        </table>
                        <br>
                        <a href='https://wa.me/52{$data->telefono}' style='display: block; text-align: center; background-color: #25D366; color: white; text-decoration: none; padding: 12px; border-radius: 6px; font-weight: bold;'>Contactar por WhatsApp</a>
                    </div>
                </div>";
                $mail->send();


                // --- CORREO 2: CONFIRMACIÓN PARA EL CLIENTE ---
                $mail->clearAddresses(); 
                $mail->clearCCs();
                $mail->addAddress($data->correo); 
                
                $mail->Subject = '✅ Cita Confirmada - Karen Martínez';
                
                // NUEVO DISEÑO PREMIUM PARA EL CLIENTE
                $mail->Body = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='UTF-8'>
                </head>
                <body style='margin: 0; padding: 0; background-color: #f4f7f6; font-family: Arial, sans-serif;'>
                    <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #f4f7f6; padding: 20px;'>
                        <tr>
                            <td align='center'>
                                <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); max-width: 600px; margin: 0 auto;'>
                                    
                                    <tr>
                                        <td align='center' style='padding: 30px 20px; border-bottom: 3px solid #E76627;'>
                                            <img src='https://karensadasi.com.mx/logokaren.png' alt='Karen Martínez' width='180' style='display: block; max-width: 100%; height: auto;'>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td align='center' style='background-color: #293F71; padding: 20px;'>
                                            <h1 style='color: #ffffff; margin: 0; font-size: 24px; font-weight: normal; letter-spacing: 1px;'>¡Cita Confirmada! ✅</h1>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td style='padding: 40px 30px;'>
                                            <h2 style='color: #293F71; margin-top: 0; font-size: 20px;'>Hola, {$data->nombre} 👋</h2>
                                            <p style='color: #555555; font-size: 16px; line-height: 1.6; margin-bottom: 25px;'>
                                                Hemos recibido tu solicitud correctamente. Me emociona mucho poder ayudarte a dar este gran paso hacia tu nuevo patrimonio.
                                            </p>

                                            <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #f8fafc; border-left: 4px solid #E76627; border-radius: 4px;'>
                                                <tr>
                                                    <td style='padding: 20px;'>
                                                        <p style='margin: 0 0 12px 0; font-size: 15px; color: #444;'><strong style='color: #293F71;'>📅 Fecha:</strong> {$data->fecha}</p>
                                                        <p style='margin: 0 0 12px 0; font-size: 15px; color: #444;'><strong style='color: #293F71;'>⏰ Hora:</strong> {$data->hora}</p>
                                                        <p style='margin: 0 0 12px 0; font-size: 15px; color: #444;'><strong style='color: #293F71;'>🏠 Modelo:</strong> {$data->modeloInteres}</p>
                                                        <p style='margin: 0; font-size: 15px; color: #444;'><strong style='color: #293F71;'>💳 Crédito:</strong> {$data->tipoCredito}</p>
                                                    </td>
                                                </tr>
                                            </table>

                                            <p style='color: #555555; font-size: 16px; line-height: 1.6; margin-top: 25px;'>
                                                En breve me pondré en contacto contigo al <strong>{$data->telefono}</strong> para enviarte la ubicación exacta y confirmar los detalles.
                                            </p>

                                            <table width='100%' border='0' cellspacing='0' cellpadding='0' style='margin-top: 35px;'>
                                                <tr>
                                                    <td align='center'>
                                                        <a href='https://wa.me/525614680620' style='background-color: #25D366; color: #ffffff; text-decoration: none; padding: 14px 30px; border-radius: 8px; font-weight: bold; font-size: 16px; display: inline-block;'>Hablemos por WhatsApp</a>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td align='center' style='background-color: #f8fafc; padding: 25px 20px; border-top: 1px solid #eeeeee;'>
                                            <p style='color: #888888; font-size: 13px; margin: 0; line-height: 1.5;'>
                                                <strong>Karén Martínez</strong><br>
                                                Asesora Inmobiliaria Certificada | Grupo Sadasi<br>
                                                <a href='https://karensadasi.com.mx' style='color: #E76627; text-decoration: none;'>www.karensadasi.com.mx</a>
                                            </p>
                                        </td>
                                    </tr>

                                </table>
                            </td>
                        </tr>
                    </table>
                </body>
                </html>
                ";
                $mail->send();

                echo json_encode(["status" => "success", "message" => "Cita guardada y correos enviados"]);

            } catch (Exception $e) {
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