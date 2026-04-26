<?php
/**
 * MONCAO SECURE - Horario
 * Ver horario semanal
 */

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';

$pageTitle = 'Horario';
$userId = $_SESSION['user_id'];

try {
    $pdo = getDB();
    
    // Obtener horarios del usuario
    $stmt = $pdo->prepare('SELECT * FROM horarios WHERE user_id = ? ORDER BY dia_semana');
    $stmt->execute([$userId]);
    $horarios = $stmt->fetchAll();
    
    // Fichajes del mes actual
    $stmt = $pdo->prepare('SELECT SUM(TIMESTAMPDIFF(HOUR, hora_entrada, hora_salida)) as horas 
                          FROM fichajes 
                          WHERE user_id = ? 
                          AND MONTH(fecha) = MONTH(CURDATE())
                          AND YEAR(fecha) = YEAR(CURDATE())
                          AND hora_salida IS NOT NULL');
    $stmt->execute([$userId]);
    $horasMes = $stmt->fetch()['horas'] ?? 0;
    
    // Horas extra del mes
    $stmt = $pdo->prepare('SELECT SUM(horas_extra) as horas_extra 
                          FROM fichajes 
                          WHERE user_id = ? 
                          AND MONTH(fecha) = MONTH(CURDATE())
                          AND YEAR(fecha) = YEAR(CURDATE())');
    $stmt->execute([$userId]);
    $horasExtra = $stmt->fetch()['horas_extra'] ?? 0;
    
    // Días de vacaciones del mes
    $stmt = $pdo->prepare('SELECT COUNT(*) as dias 
                          FROM vacaciones 
                          WHERE user_id = ? 
                          AND MONTH(fecha_inicio) = MONTH(CURDATE())
                          AND YEAR(fecha_inicio) = YEAR(CURDATE())
                          AND estado = "aprobada"');
    $stmt->execute([$userId]);
    $diasVacaciones = $stmt->fetch()['dias'] ?? 0;
    
} catch (PDOException $e) {
    error_log($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - MONCAO SECURE</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <style>
        :root {
            --color-primary: #1A73E8;
            --color-secondary: #F1F3F4;
            --color-accent: #34A853;
            --color-danger: #EA4335;
            --color-dark: #202124;
            --color-white: #FFFFFF;
            --color-warning: #FBBC04;
            --color-gray: #5F6368;
        }
        body { font-family: 'Inter', sans-serif; background-color: var(--color-secondary); }
    </style>
</head>
<body>

<?php include '../includes/navbar.php'; ?>

<main>
    <div class="container-fluid py-4">
        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-value"><?php echo number_format($horasMes, 1); ?>h</div>
                    <div class="stats-label">Horas trabajadas este mes</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-value"><?php echo number_format($horasExtra, 2); ?>h</div>
                    <div class="stats-label">Horas extra</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-value"><?php echo $diasVacaciones; ?></div>
                    <div class="stats-label">Días de vacaciones</div>
                </div>
            </div>
        </div>
        
        <!-- Horario Semanal -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-calendar me-2"></i>Horario Semanal
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Día</th>
                                        <th>Hora Entrada</th>
                                        <th>Hora Salida</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $dias = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
                                    $horarioMap = [];
                                    foreach ($horarios as $h) {
                                        $horarioMap[$h['dia_semana']] = $h;
                                    }
                                    
                                    for ($dia = 1; $dia <= 5; $dia++): 
                                        $h = $horarioMap[$dia] ?? null;
                                    ?>
                                    <tr>
                                        <td><?php echo $dias[$dia]; ?></td>
                                        <td><?php echo $h ? $h['hora_inicio'] : '09:00:00'; ?></td>
                                        <td><?php echo $h ? $h['hora_fin'] : '17:00:00'; ?></td>
                                        <td>
                                            <?php if (date('N') == $dia): ?>
                                            <span class="badge bg-primary">Hoy</span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary">Programado</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

</body>
</html>