<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: /index.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Informes';
$userId = $_SESSION['user_id'];

$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fechaFin    = $_GET['fecha_fin']    ?? date('Y-m-d');

$stats = ['dias_trabajados' => 0, 'horas_totales' => 0, 'horas_extra' => 0];
$diasVacaciones = 0;
$horasSemana    = [];
$horasProyecto  = [];
$puntualidad    = ['puntual' => 0, 'tarde' => 0];

try {
    $pdo = getDB();

    // Stats generales
    $stmt = $pdo->prepare('SELECT COUNT(*) as dias_trabajados,
        ROUND(SUM(TIMESTAMPDIFF(MINUTE, hora_entrada, hora_salida))/60, 1) as horas_totales,
        SUM(horas_extra) as horas_extra
        FROM fichajes WHERE user_id = ? AND fecha BETWEEN ? AND ? AND hora_salida IS NOT NULL');
    $stmt->execute([$userId, $fechaInicio, $fechaFin]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Vacaciones
    $stmt = $pdo->prepare('SELECT COUNT(*) as dias FROM vacaciones WHERE user_id = ? AND estado = "aprobada" AND fecha_inicio BETWEEN ? AND ?');
    $stmt->execute([$userId, $fechaInicio, $fechaFin]);
    $diasVacaciones = $stmt->fetch()['dias'] ?? 0;

    // Horas por semana
    $stmt = $pdo->prepare('SELECT DATE_FORMAT(MIN(fecha), "%d/%m") as semana_label,
        ROUND(SUM(TIMESTAMPDIFF(MINUTE, hora_entrada, hora_salida))/60, 1) as horas
        FROM fichajes WHERE user_id = ? AND fecha BETWEEN ? AND ? AND hora_salida IS NOT NULL
        GROUP BY YEARWEEK(fecha, 1) ORDER BY YEARWEEK(fecha, 1)');
    $stmt->execute([$userId, $fechaInicio, $fechaFin]);
    $horasSemana = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Horas por proyecto
    $stmt = $pdo->prepare('SELECT COALESCE(p.nombre, "Sin proyecto") as proyecto,
        ROUND(SUM(TIMESTAMPDIFF(MINUTE, f.hora_entrada, f.hora_salida))/60, 1) as horas
        FROM fichajes f LEFT JOIN proyectos p ON f.proyecto_id = p.id
        WHERE f.user_id = ? AND f.fecha BETWEEN ? AND ? AND f.hora_salida IS NOT NULL
        GROUP BY p.nombre ORDER BY horas DESC');
    $stmt->execute([$userId, $fechaInicio, $fechaFin]);
    $horasProyecto = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Puntualidad
    $stmt = $pdo->prepare('SELECT
        SUM(CASE WHEN tarde = 0 THEN 1 ELSE 0 END) as puntual,
        SUM(CASE WHEN tarde = 1 THEN 1 ELSE 0 END) as tarde
        FROM fichajes WHERE user_id = ? AND fecha BETWEEN ? AND ?');
    $stmt->execute([$userId, $fechaInicio, $fechaFin]);
    $puntualidad = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
}

// Si no hay datos reales, generar datos de ejemplo para mostrar las gráficas
$sinDatos = empty($horasSemana) && empty($horasProyecto);
if ($sinDatos) {
    $horasSemana = [
        ['semana_label' => 'Sem 1', 'horas' => 38.5],
        ['semana_label' => 'Sem 2', 'horas' => 40.0],
        ['semana_label' => 'Sem 3', 'horas' => 37.0],
        ['semana_label' => 'Sem 4', 'horas' => 41.5],
    ];
    $horasProyecto = [
        ['proyecto' => 'App Interna RRHH', 'horas' => 60],
        ['proyecto' => 'Rediseño Web', 'horas' => 45],
        ['proyecto' => 'Sin proyecto', 'horas' => 15],
    ];
    $puntualidad = ['puntual' => 18, 'tarde' => 2];
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
        :root { --color-primary:#1A73E8; --color-secondary:#F1F3F4; --color-gray:#5F6368; --color-white:#FFFFFF; }
        body { font-family:'Inter',sans-serif; background-color:var(--color-secondary); }
        .stats-card { background:var(--color-white); border-radius:10px; padding:20px; margin-bottom:15px; box-shadow:0 2px 5px rgba(0,0,0,0.1); text-align:center; }
        .stats-card .stats-value { font-size:2.2em; font-weight:700; color:var(--color-primary); margin-bottom:5px; }
        .stats-card .stats-label { font-size:0.9em; color:var(--color-gray); text-transform:uppercase; }
        .chart-container { position:relative; height:280px; }
        .demo-badge { background:#fff3cd; color:#856404; border:1px solid #ffc107; border-radius:6px; padding:8px 14px; font-size:0.85em; margin-bottom:16px; display:inline-block; }
    </style>
</head>
<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<main>
    <div class="container-fluid py-4">

        <?php if ($sinDatos): ?>
        <div class="demo-badge"><i class="fas fa-info-circle me-1"></i>No tienes fichajes en el período seleccionado. Las gráficas muestran datos de ejemplo. Ficha a diario para ver tus datos reales.</div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-filter me-2"></i>Seleccionar Período</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control" id="fecha_inicio" value="<?php echo $fechaInicio; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Fecha Fin</label>
                        <input type="date" class="form-control" id="fecha_fin" value="<?php echo $fechaFin; ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button class="btn btn-primary w-100" onclick="buscar()">
                            <i class="fas fa-search me-2"></i>Buscar
                        </button>
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

        <!-- Gráficas -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-header"><i class="fas fa-chart-bar me-2"></i>Horas por Semana</div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartSemanas"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-header"><i class="fas fa-chart-pie me-2"></i>Horas por Proyecto</div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartProyectos"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-header"><i class="fas fa-clock me-2"></i>Puntualidad</div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartPuntualidad"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exportar -->
        <a href="../pdf/generar_informe.php?fecha_inicio=<?php echo $fechaInicio; ?>&fecha_fin=<?php echo $fechaFin; ?>" class="btn btn-primary" target="_blank">
            <i class="fas fa-file-pdf me-2"></i>Exportar PDF
        </a>

    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    function buscar() {
        const fi = document.getElementById('fecha_inicio').value;
        const ff = document.getElementById('fecha_fin').value;
        window.location.href = '/modules/informes.php?fecha_inicio=' + fi + '&fecha_fin=' + ff;
    }

    const colores = ['#1A73E8','#34A853','#EA4335','#FBBC04','#9334e9','#00BCD4','#FF5722'];

    // Horas por semana
    new Chart(document.getElementById('chartSemanas'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($horasSemana, 'semana_label')); ?>,
            datasets: [{
                label: 'Horas',
                data: <?php echo json_encode(array_column($horasSemana, 'horas')); ?>,
                backgroundColor: '#1A73E8',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, max: 50 } }
        }
    });

    // Horas por proyecto
    new Chart(document.getElementById('chartProyectos'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($horasProyecto, 'proyecto')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($horasProyecto, 'horas')); ?>,
                backgroundColor: colores
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    // Puntualidad
    new Chart(document.getElementById('chartPuntualidad'), {
        type: 'pie',
        data: {
            labels: ['Puntual', 'Tarde'],
            datasets: [{
                data: [<?php echo (int)($puntualidad['puntual'] ?? 0); ?>, <?php echo (int)($puntualidad['tarde'] ?? 0); ?>],
                backgroundColor: ['#34A853', '#EA4335']
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
</script>
</body>
</html>
