<?php
/**
 * MONCAO SECURE - Informes
 * Informes personales con gráficos
 */

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';

$pageTitle = 'Informes';
$userId = $_SESSION['user_id'];

// Fechas por defecto (último mes)
$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');

try {
    $pdo = getDB();
    
    // Estadísticas del período
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
    
    // Días de vacaciones
    $stmt = $pdo->prepare('SELECT COUNT(*) as dias FROM vacaciones WHERE user_id = ? AND estado = "aprobada" AND fecha_inicio BETWEEN ? AND ?');
    $stmt->execute([$userId, $fechaInicio, $fechaFin]);
    $diasVacaciones = $stmt->fetch()['dias'] ?? 0;
    
    // Datos para gráfico de barras (horas por semana)
    $stmt = $pdo->prepare('SELECT 
        WEEK(fecha) as semana,
        SUM(TIMESTAMPDIFF(HOUR, hora_entrada, hora_salida)) as horas
        FROM fichajes 
        WHERE user_id = ? 
        AND fecha BETWEEN ? AND ?
        AND hora_salida IS NOT NULL
        GROUP BY WEEK(fecha)
        ORDER BY semana');
    $stmt->execute([$userId, $fechaInicio, $fechaFin]);
    $horasSemana = $stmt->fetchAll();
    
    // Datos para gráfico de dona (horas por proyecto)
    $stmt = $pdo->prepare('SELECT 
        p.nombre as proyecto,
        SUM(TIMESTAMPDIFF(HOUR, f.hora_entrada, f.hora_salida)) as horas
        FROM fichajes f
        LEFT JOIN proyectos p ON f.proyecto_id = p.id
        WHERE f.user_id = ? 
        AND f.fecha BETWEEN ? AND ?
        AND f.hora_salida IS NOT NULL
        GROUP BY p.nombre
        ORDER BY horas DESC');
    $stmt->execute([$userId, $fechaInicio, $fechaFin]);
    $horasProyecto = $stmt->fetchAll();
    
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
    <link href="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" rel="stylesheet">
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
        <!-- Filtros -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-filter me-2"></i>Seleccionar Período
                    </div>
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-4">
                                <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?php echo $fechaInicio; ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="fecha_fin" class="form-label">Fecha Fin</label>
                                <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?php echo $fechaFin; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-2"></i>Buscar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-value"><?php echo $stats['dias_trabajados'] ?? 0; ?></div>
                    <div class="stats-label">Días trabajados</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-value"><?php echo number_format($stats['horas_totales'] ?? 0, 1); ?>h</div>
                    <div class="stats-label">Horas totales</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-value"><?php echo number_format($stats['horas_extra'] ?? 0, 2); ?>h</div>
                    <div class="stats-label">Horas extra</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-value"><?php echo $diasVacaciones; ?></div>
                    <div class="stats-label">Días de vacaciones</div>
                </div>
            </div>
        </div>
        
        <!-- Gráficos -->
        <div class="row mb-4">
            <!-- Gráfico de Barras -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-bar me-2"></i>Horas por Semana
                    </div>
                    <div class="card-body">
                        <canvas id="chartSemanas"></canvas>
                    </div>
                </div>
            </div>
            <!-- Gráfico de Dona -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-pie me-2"></i>Horas por Proyecto
                    </div>
                    <div class="card-body">
                        <canvas id="chartProyectos"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Exportar -->
        <div class="row">
            <div class="col-12">
                <a href="../pdf/generar_informe.php?fecha_inicio=<?php echo $fechaInicio; ?>&fecha_fin=<?php echo $fechaFin; ?>" class="btn btn-primary" target="_blank">
                    <i class="fas fa-file-pdf me-2"></i>Exportar PDF
                </a>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    // Gráfico de barras - Horas por semana
    const ctxSemanas = document.getElementById('chartSemanas');
    if (ctxSemanas) {
        new Chart(ctxSemanas, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($horasSemana, 'semana')); ?>,
                datasets: [{
                    label: 'Horas',
                    data: <?php echo json_encode(array_column($horasSemana, 'horas')); ?>,
                    backgroundColor: '#1A73E8'
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } }
            }
        });
    }
    
    // Gráfico de dona - Horas por proyecto
    const ctxProyectos = document.getElementById('chartProyectos');
    if (ctxProyectos) {
        new Chart(ctxProyectos, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($horasProyecto, 'proyecto')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($horasProyecto, 'horas')); ?>,
                    backgroundColor: ['#1A73E8', '#34A853', '#EA4335', '#FBBC04', '#9334e9']
                }]
            },
            options: {
                responsive: true
            }
        });
    }
</script>

</body>
</html>