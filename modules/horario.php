<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: /index.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Horario';
$userId = $_SESSION['user_id'];
$horarios = [];
$horasMes = 0;
$horasExtra = 0;
$diasVacaciones = 0;
$fichajes = [];

try {
    $pdo = getDB();

    // Insertar horario por defecto si el usuario no tiene ninguno
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM horarios WHERE user_id = ?');
    $stmt->execute([$userId]);
    if ($stmt->fetchColumn() == 0) {
        for ($dia = 1; $dia <= 5; $dia++) {
            $ins = $pdo->prepare('INSERT INTO horarios (user_id, dia_semana, hora_inicio, hora_fin) VALUES (?, ?, ?, ?)');
            $ins->execute([$userId, $dia, '09:00:00', '17:00:00']);
        }
    }

    // Obtener horarios del usuario
    $stmt = $pdo->prepare('SELECT * FROM horarios WHERE user_id = ? ORDER BY dia_semana');
    $stmt->execute([$userId]);
    $horarios = $stmt->fetchAll();

    // Fichajes de esta semana (lunes a hoy)
    $stmt = $pdo->prepare('SELECT * FROM fichajes 
        WHERE user_id = ? 
        AND YEARWEEK(fecha, 1) = YEARWEEK(CURDATE(), 1)
        ORDER BY fecha');
    $stmt->execute([$userId]);
    $fichajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Horas mes
    $stmt = $pdo->prepare('SELECT SUM(TIMESTAMPDIFF(MINUTE, hora_entrada, hora_salida))/60 as horas 
                          FROM fichajes WHERE user_id = ? 
                          AND MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())
                          AND hora_salida IS NOT NULL');
    $stmt->execute([$userId]);
    $horasMes = $stmt->fetch()['horas'] ?? 0;

    // Horas extra mes
    $stmt = $pdo->prepare('SELECT SUM(horas_extra) as horas_extra FROM fichajes 
                          WHERE user_id = ? AND MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())');
    $stmt->execute([$userId]);
    $horasExtra = $stmt->fetch()['horas_extra'] ?? 0;

    // Vacaciones aprobadas
    $stmt = $pdo->prepare('SELECT COUNT(*) as dias FROM vacaciones WHERE user_id = ? AND estado = "aprobada"
                          AND MONTH(fecha_inicio) = MONTH(CURDATE()) AND YEAR(fecha_inicio) = YEAR(CURDATE())');
    $stmt->execute([$userId]);
    $diasVacaciones = $stmt->fetch()['dias'] ?? 0;

} catch (PDOException $e) {
    error_log($e->getMessage());
}

// Mapear horarios y fichajes por día
$horarioMap = [];
foreach ($horarios as $h) {
    $horarioMap[$h['dia_semana']] = $h;
}
$fichajeMap = [];
foreach ($fichajes as $f) {
    $diaSemana = date('N', strtotime($f['fecha'])); // 1=Lun, 7=Dom
    $fichajeMap[$diaSemana] = $f;
}

$dias = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
$hoyDia = date('N'); // día actual 1-7
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
        .stats-card { background: var(--color-white); border-radius: 10px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; }
        .stats-card .stats-value { font-size: 2.2em; font-weight: 700; color: var(--color-primary); margin-bottom: 5px; }
        .stats-card .stats-label { font-size: 0.9em; color: var(--color-gray); text-transform: uppercase; }
        .row-hoy { background-color: #e8f0fe !important; font-weight: 600; }
        .row-ok td { color: #34A853; }
        .row-tarde td { color: #EA4335; }
        .badge-ok { background-color: #34A853; }
        .badge-tarde { background-color: #EA4335; }
        .hora-real { font-size: 0.85em; color: var(--color-gray); }
    </style>
</head>
<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

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
                        <i class="fas fa-calendar me-2"></i>Horario Semanal — Semana actual
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Día</th>
                                        <th>Hora prevista entrada</th>
                                        <th>Hora prevista salida</th>
                                        <th>Entrada real</th>
                                        <th>Salida real</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for ($dia = 1; $dia <= 5; $dia++):
                                        $h = $horarioMap[$dia] ?? null;
                                        $horaInicio = $h ? $h['hora_inicio'] : '09:00:00';
                                        $horaFin    = $h ? $h['hora_fin']   : '17:00:00';
                                        $f = $fichajeMap[$dia] ?? null;
                                        $esHoy = ($hoyDia == $dia);

                                        // Determinar estado
                                        $estadoClass = '';
                                        $badgeClass  = 'bg-secondary';
                                        $badgeText   = 'Sin fichar';

                                        if ($f) {
                                            $entradaReal = $f['hora_entrada'];
                                            $salidaReal  = $f['hora_salida'];
                                            $tarde = strtotime($entradaReal) > strtotime($horaInicio);
                                            $antesHora = $salidaReal && strtotime($salidaReal) < strtotime($horaFin);

                                            if ($tarde || $antesHora) {
                                                $estadoClass = 'row-tarde';
                                                $badgeClass  = 'badge-tarde';
                                                $badgeText   = $tarde ? 'Llegó tarde' : 'Salió antes';
                                            } else {
                                                $estadoClass = 'row-ok';
                                                $badgeClass  = 'badge-ok';
                                                $badgeText   = 'Cumplido ✓';
                                            }
                                        }
                                    ?>
                                    <tr class="<?php echo $esHoy ? 'row-hoy' : ''; ?> <?php echo $estadoClass; ?>">
                                        <td>
                                            <?php echo $dias[$dia]; ?>
                                            <?php if ($esHoy): ?>
                                                <span class="badge bg-primary ms-1">Hoy</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo substr($horaInicio, 0, 5); ?></td>
                                        <td><?php echo substr($horaFin, 0, 5); ?></td>
                                        <td><?php echo $f ? substr($f['hora_entrada'], 0, 5) : '—'; ?></td>
                                        <td><?php echo ($f && $f['hora_salida']) ? substr($f['hora_salida'], 0, 5) : '—'; ?></td>
                                        <td>
                                            <span class="badge <?php echo $badgeClass; ?>">
                                                <?php echo $badgeText; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <p class="text-muted mt-2 small"><i class="fas fa-info-circle me-1"></i>Se muestra la semana actual. Ficha a diario para ver tu estado en tiempo real.</p>
            </div>
        </div>

    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
