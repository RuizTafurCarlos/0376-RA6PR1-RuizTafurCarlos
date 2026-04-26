<?php
/**
 * MONCAO SECURE - Notificaciones por Email
 * Funciones para enviar emails usando PHPMailer
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Enviar email de alerta de retraso
 * @param int $userId ID del usuario
 * @param int $minutosRetraso Minutos de retraso
 * @param string $horaEntrada Hora de entrada
 */
function enviarAlertaRetraso($userId, $minutosRetraso, $horaEntrada) {
    try {
        $pdo = getDB();
        
        // Obtener datos del usuario
        $stmt = $pdo->prepare('SELECT u.*, d.nombre as depto_nombre FROM users u LEFT JOIN departamentos d ON u.departamento_id = d.id WHERE u.id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) return;
        
        // Obtener admins del departamento y superadmin
        $stmt = $pdo->query('SELECT email FROM users WHERE rol IN ("admin", "superadmin") AND activo = TRUE');
        $admins = $stmt->fetchAll();
        
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'moncao.secure@gmail.com';
        $mail->Password = 'tu_password_smtp';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->setFrom('moncao.secure@gmail.com', 'MONCAO SECURE');
        $mail->addAddress('rrhh@moncao.com');
        
        foreach ($admins as $admin) {
            $mail->addAddress($admin['email']);
        }
        
        $mail->Subject = 'ALERTA: Usuario llegó tarde - MONCAO SECURE';
        $mail->Body = "Se ha detectado un retraso en el fichaje:\n\n";
        $mail->Body .= "Usuario: " . $user['nombre'] . "\n";
        $mail->Body .= "Departamento: " . $user['depto_nombre'] . "\n";
        $mail->Body .= "Hora de entrada: " . $horaEntrada . "\n";
        $mail->Body .= "Minutos de retraso: " . $minutosRetraso . "\n";
        $mail->Body .= "\nFecha: " . date('d/m/Y H:i:s');
        
        $mail->send();
    } catch (Exception $e) {
        error_log("Error al enviar email: " . $e->getMessage());
    }
}

/**
 * Enviar email de notificación de solicitud
 * @param int $userId ID del usuario
 * @param string $tipo Tipo de solicitud
 */
function enviarNotificacionSolicitud($userId, $tipo) {
    try {
        $pdo = getDB();
        
        $stmt = $pdo->prepare('SELECT u.nombre, u.departamento_id FROM users u WHERE u.id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        $stmt = $pdo->query('SELECT email FROM users WHERE rol IN ("admin", "superadmin") AND activo = TRUE');
        $admins = $stmt->fetchAll();
        
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'moncao.secure@gmail.com';
        $mail->Password = 'tu_password_smtp';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->setFrom('moncao.secure@gmail.com', 'MONCAO SECURE');
        
        foreach ($admins as $admin) {
            $mail->addAddress($admin['email']);
        }
        
        $tipos = [
            'vacaciones' => 'Solicitud de Vacaciones',
            'baja_temporal' => 'Baja Temporal',
            'baja_voluntaria' => 'Baja Voluntaria',
            'despido' => 'Despido',
            'cambio_horario' => 'Cambio de Horario'
        ];
        
        $mail->Subject = 'NUEVA SOLICITUD: ' . ($tipos[$tipo] ?? $tipo) . ' - MONCAO SECURE';
        $mail->Body = "Se ha recibido una nueva solicitud:\n\n";
        $mail->Body .= "Usuario: " . $user['nombre'] . "\n";
        $mail->Body .= "Tipo: " . ($tipos[$tipo] ?? $tipo) . "\n";
        $mail->Body .= "\nFecha: " . date('d/m/Y H:i:s');
        
        $mail->send();
    } catch (Exception $e) {
        error_log("Error al enviar email: " . $e->getMessage());
    }
}

/**
 * Enviar email de respuesta de solicitud
 * @param int $userId ID del usuario
 * @param string $tipo Tipo de solicitud
 * @param string $estado Estado (aprobada/denegada)
 */
function enviarRespuestaSolicitud($userId, $tipo, $estado) {
    try {
        $pdo = getDB();
        
        $stmt = $pdo->prepare('SELECT email, nombre FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) return;
        
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'moncao.secure@gmail.com';
        $mail->Password = 'tu_password_smtp';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->setFrom('moncao.secure@gmail.com', 'MONCAO SECURE');
        $mail->addAddress($user['email']);
        
        $mail->Subject = 'Tu solicitud ha sido ' . $estado . ' - MONCAO SECURE';
        $mail->Body = "Hola " . $user['nombre'] . ",\n\n";
        $mail->Body .= "Tu solicitud de " . $tipo . " ha sido " . $estado . ".\n\n";
        $mail->Body .= "Saludos,\n";
        $mail->Body .= "MONCAO SECURE";
        
        $mail->send();
    } catch (Exception $e) {
        error_log("Error al enviar email: " . $e->getMessage());
    }
}