<?php
/**
 * MONCAO SECURE - Generar Informe PDF
 * Genera informes en PDF usando TCPDF
 */

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

require_once '../vendor/autoload.php';
require_once '../config/db.php';

use TCPDF\TCPDF;

$userId = $_SESSION['user_id'];
$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');

try {
    $pdo = getDB();
    
    // Datos del usuario
    $stmt = $pdo->prepare('SELECT u.*, d.nombre as depto_nombre FROM users u LEFT JOIN departamentos d ON u.departamento_id = d.id WHERE u.id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    // Fichajes del período
    $stmt = $pdo->prepare('SELECT f.*, p.nombre as proyecto_nombre 
                          FROM fichajes f 
                          LEFT JOIN proyectos p ON f.proyecto_id = p.id
                          WHERE f.user_id = ? 
                          AND f.fecha BETWEEN ? AND ?
                          ORDER BY f.fecha');
    $stmt->execute([$userId, $fechaInicio, $fechaFin]);
    $fichajes = $stmt->fetchAll();
    
    // Estadísticas
    $stmt = $pdo->prepare('SELECT 
        COUNT(*) as dias_trabajados,
        SUM(TIMESTAMPDIFF(HOUR, hora_entrada, hora_salida)) as horas_totales,
        SUM(horas_extra) as horas_extra
        FROM fichajes 
        WHERE user_id = ? 
        AND fecha BETWEEN ? AND ?
        AND hora_salida IS NOT NULL');
    $stmt->execute([$userId, $fechaInicio, $fechaFin]);
    $stats = $stmt->fetch();
    
    // Crear PDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    $pdf->SetCreator('MONCAO SECURE');
    $pdf->SetAuthor('MONCAO SECURE');
    $pdf->SetTitle('Informe de Fichajes');
    
    $pdf->AddPage();
    
    // Encabezado
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'MONCAO SECURE - Informe de Fichajes', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 8, 'Periodo: ' . date('d/m/Y', strtotime($fechaInicio)) . ' - ' . date('d/m/Y', strtotime($fechaFin)), 0, 1, 'C');
    $pdf->Ln(5);
    
    // Datos del empleado
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Datos del Empleado', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(50, 7, 'Nombre:', 0, 0);
    $pdf->Cell(0, 7, $user['nombre'], 0, 1);
    $pdf->Cell(50, 7, 'Departamento:', 0, 0);
    $pdf->Cell(0, 7, $user['depto_nombre'] ?? '-', 0, 1);
    $pdf->Ln(5);
    
    // Resumen
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Resumen', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(60, 7, 'Dias trabajados:', 0, 0);
    $pdf->Cell(0, 7, $stats['dias_trabajados'] ?? 0, 0, 1);
    $pdf->Cell(60, 7, 'Horas totales:', 0, 0);
    $pdf->Cell(0, 7, number_format($stats['horas_totales'] ?? 0, 1) . ' horas', 0, 1);
    $pdf->Cell(60, 7, 'Horas extra:', 0, 0);
    $pdf->Cell(0, 7, number_format($stats['horas_extra'] ?? 0, 2) . ' horas', 0, 1);
    $pdf->Ln(5);
    
    // Tabla de fichajes
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Detalle de Fichajes', 0, 1, 'L');
    
    // Encabezado de tabla
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(26, 115, 232);
    $pdf->SetTextColor(255);
    $pdf->Cell(25, 7, 'Fecha', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Entrada', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Salida', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'Proyecto', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Estado', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Horas Extra', 1, 1, 'C', true);
    
    // Datos
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(0);
    
    foreach ($fichajes as $f) {
        $pdf->Cell(25, 6, date('d/m/Y', strtotime($f['fecha'])), 1, 0, 'C');
        $pdf->Cell(25, 6, $f['hora_entrada'] ?: '-', 1, 0, 'C');
        $pdf->Cell(25, 6, $f['hora_salida'] ?: '-', 1, 0, 'C');
        $pdf->Cell(40, 6, $f['proyecto_nombre'] ?? '-', 1, 0, 'C');
        
        $estado = $f['tarde'] ? 'Tarde' : ($f['hora_salida'] ? 'OK' : 'Pendiente');
        $pdf->Cell(25, 6, $estado, 1, 0, 'C');
        $pdf->Cell(25, 6, number_format($f['horas_extra'], 2), 1, 1, 'C');
    }
    
    // Footer
    $pdf->SetY(-20);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'Generado el ' . date('d/m/Y H:i:s') . ' por MONCAO SECURE', 0, 0, 'C');
    
    $pdf->Output('informe_fichajes.pdf', 'D');
    
} catch (Exception $e) {
    error_log($e->getMessage());
    echo 'Error al generar el informe.';
}