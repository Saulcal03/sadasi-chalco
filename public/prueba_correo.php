<?php
// public/prueba_correo.php

// 1. CONFIGURA ESTO
$para = "saulcalderon@ollintem.com.mx"; // Tu correo personal
$titulo = "Prueba de Servidor Plesk";
$mensaje = "Si lees esto, tu servidor SÍ puede enviar correos.";

// 2. CONFIGURACIÓN DEL REMITENTE (IMPORTANTE)
// Usamos una dirección genérica de tu dominio para engañar al filtro
$de = "no-reply@ollintem.com.mx"; 

$cabeceras = "From: " . $de . "\r\n" .
             "Reply-To: " . $de . "\r\n" .
             "X-Mailer: PHP/" . phpversion();

// 3. INTENTAR ENVIAR
echo "<h1>Diagnóstico de Correo</h1>";
echo "<p>Intentando enviar correo a: <strong>$para</strong>...</p>";

if (mail($para, $titulo, $mensaje, $cabeceras)) {
    echo "<h2 style='color:green'>✅ ÉXITO: La función mail() se ejecutó correctamente.</h2>";
    echo "<p>Revisa tu bandeja de entrada y SPAM en 1 minuto.</p>";
    echo "<p><em>Nota: Si sale verde pero NO llega, es que Gmail lo está bloqueando por falta de certificado SPF/DKIM en tu dominio.</em></p>";
} else {
    echo "<h2 style='color:red'>❌ ERROR: El servidor rechazó el envío.</h2>";
    echo "<p>Posibles causas:</p>";
    echo "<ul>
            <li>La función mail() está desactivada en Plesk.</li>
            <li>El puerto de correo (25 o 587) está bloqueado.</li>
            <li>Necesitas configurar un servidor SMTP real.</li>
          </ul>";
}
?>