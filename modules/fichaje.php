<?php
/**
 * MONCAO SECURE - Fichaje
 * Registro de entrada y salida
 */

session_start();

// Verificar autenticación
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';

$pageTitle = 'Fichaje';
$userId = $_SESSION['user_id'];

// Obtener proyectos asignados al usuario
try {
    $pdo = getDB();
    
    $stmt = $pdo->prepare('SELECT p.id, p.nombre FROM proyectos p 
                          JOIN proyecto_usuario pu ON p.id = pu.proyecto_id 
                          WHERE pu.user_id = ? AND p.activo = TRUE');
    $stmt->execute([$userId]);
    $proyectos = $stmt->fetchAll();
    
    // Fichaje de hoy
    $stmt = $pdo->prepare('SELECT * FROM fichajes WHERE user_id = ? AND fecha = CURDATE()');
    $stmt->execute([$userId]);
    $fichajeHoy = $stmt->fetch();
    
    // Obtener horario del usuario para hoy
    $diaSemana = date('N'); // 1=Lunes, 5=Viernes
    $stmt = $pdo->prepare('SELECT * FROM horarios WHERE user_id = ? AND dia_semana = ?');
    $stmt->execute([$userId, $diaSemana]);
    $horario = $stmt->fetch();
    
    // Fichajes de la semana
    $stmt = $pdo->prepare('SELECT f.*, p.nombre as proyecto_nombre 
                          FROM fichajes f 
                          LEFT JOIN proyectos p ON f.proyecto_id = p.id
                          WHERE f.user_id = ? 
                          AND f.fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                          ORDER BY f.fecha DESC');
    $stmt->execute([$userId]);
    $fichajesSemana = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Error al cargar los datos.';
}

// Procesar acciones de fichaje
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config/db.php';
    require_once '../mail/notificaciones.php';
    
    try {
        $pdo = getDB();
        $action = $_POST['action'] ?? '';
        
        if ($action === 'entrada') {
            $proyectoId = !empty($_POST['proyecto_id']) ? $_POST['proyecto_id'] : null;
            $telework = isset($_POST['telework']) ? true : false;
            $horaEntrada = date('H:i:s');
            
            // Verificar si llega tarde
            $horaLimite = '09:05:00'; // 5 minutos de margen
            $tarde = $horaEntrada > $horaLimite;
            $minutosRetraso = $tarde ? (strtotime($horaEntrada) - strtotime('09:00:00')) / 60 : 0;
            
            // Insertar fichaje de entrada
            $stmt = $pdo->prepare('INSERT INTO fichajes (user_id, proyecto_id, fecha, hora_entrada, tarde, minutos_retraso, telework) 
                                  VALUES (?, ?, CURDATE(), ?, ?, ?, ?)');
            $stmt->execute([$userId, $proyectoId, $horaEntrada, $tarde ? 1 : 0, $minutosRetraso, $telework ? 1 : 0]);
            
            $message = 'Entrada registrada a las ' . $horaEntrada . ($tarde ? ' (Tarde: ' . $minutosRetraso . ' minutos)' : '');
            $messageType = $tarde ? 'warning' : 'success';
            
            // Enviar email de alerta si llega tarde
            if ($tarde) {
                enviarAlertaRetraso($userId, $minutosRetraso, $horaEntrada);
            }
            
        } elseif ($action === 'salida') {
            $horaSalida = date('H:i:s');
            $horaLimite = '17:00:00';
            $horasExtra = $horaSalida > $horaLimite ? (strtotime($horaSalida) - strtotime('17:00:00')) / 3600 : 0;
            
            // Actualizar fichaje de hoy
            $stmt = $pdo->prepare('UPDATE fichajes SET hora_salida = ?, horas_extra = ? 
                                  WHERE user_id = ? AND fecha = CURDATE() AND hora_entrada IS NOT NULL');
            $stmt->execute([$horaSalida, $horasExtra, $userId]);
            
            $message = 'Salida registrada a las ' . $horaSalida . ($horasExtra > 0 ? ' (Horas extra: ' . number_format($horasExtra, 2) . 'h)' : '');
            $messageType = 'success';
        }
        
        // Recargar datos
        $stmt = $pdo->prepare('SELECT * FROM fichajes WHERE user_id = ? AND fecha = CURDATE()');
        $stmt->execute([$userId]);
        $fichajeHoy = $stmt->fetch();
        
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $message = 'Error al registrar el fichaje.';
        $messageType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - MONCAO SECURE</title>
    
    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
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
        <!-- Mensajes -->
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?> me-2"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Reloj y Fecha -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-4">
                        <div class="time-display" id="current-time"><?php echo date('H:i:s'); ?></div>
                        <div class="date-display" id="current-date"><?php echo date('l, d \d\e F \d\e Y'); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Botones de Fichaje -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-clock me-2"></i>Registro de Fichaje
                    </div>
                    <div class="card-body">
                        <?php if (!$fichajeHoy || !$fichajeHoy['hora_entrada']): ?>
                        <!-- Registrar Entrada -->
                        <form method="POST" action="" class="text-center">
                            <input type="hidden" name="action" value="entrada">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="proyecto_id" class="form-label">Proyecto (opcional)</label>
                                    <select class="form-select" id="proyecto_id" name="proyecto_id">
                                        <option value="">Sin proyecto específico</option>
                                        <?php foreach ($proyectos as $proyecto): ?>
                                        <option value="<?php echo $proyecto['id']; ?>"><?php echo htmlspecialchars($proyecto['nombre']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="telework" name="telework" value="1">
                                        <label class="form-check-label" for="telework">
                                            <i class="fas fa-home me-1"></i> Teletrabajo
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-fichar-entrada">
                                    <i class="fas fa-sign-in-alt me-2"></i>REGISTRAR ENTRADA
                                </button>
                            </div>
                        </form>
                        <?php elseif (!$fichajeHoy['hora_salida']): ?>
                        <!-- Registrar Salida -->
                        <form method="POST" action="" class="text-center">
                            <input type="hidden" name="action" value="salida">
                            
                            <div class="mb-3">
                                <p class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Has ficheado a las <?php echo $fichajeHoy['hora_entrada']; ?>
                                    <?php if ($fichajeHoy['tarde']): ?>
                                    <span class="badge bg-danger ms-2">Tarde (<?php echo $fichajeHoy['minutos_retraso']; ?> min)</span>
                                    <?php endif; ?>
                                    <?php if ($fichajeHoy['telework']): ?>
                                    <span class="badge bg-purple ms-2">Teltrabajo</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            
                            <button type="submit" class="btn btn-fichar-salida">
                                <i class="fas fa-sign-out-alt me-2"></i>REGISTRAR SALIDA
                            </button>
                        </form>
                        <?php else: ?>
                        <!-- Ya fichado -->
                        <div class="text-center">
                            <p class="text-muted mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Ya has completado tu jornada de hoy
                            </p>
                            <div class="row justify-content-center">
                                <div class="col-md-4">
                                    <div class="stats-card">
                                        <div class="stats-value"><?php echo $fichajeHoy['hora_entrada']; ?></div>
                                        <div class="stats-label">Entrada</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stats-card">
                                        <div class="stats-value"><?php echo $fichajeHoy['hora_salida']; ?></div>
                                        <div class="stats-label">Salida</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stats-card">
                                        <div class="stats-value"><?php echo number_format($fichajeHoy['horas_extra'], 2); ?>h</div>
                                        <div class="stats-label">Horas Extra</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Historial de la Semana -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-history me-2"></i>Historial de la Semana
                    </div>
                    <div class="card-body">
                        <?php if (empty($fichajesSemana)): ?>
                        <p class="text-muted text-center mb-0">No hay fichajes esta semana</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Entrada</th>
                                        <th>Salida</th>
                                        <th>Proyecto</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fichajesSemana as $fichaje): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($fichaje['fecha'])); ?></td>
                                        <td><?php echo $fichaje['hora_entrada'] ?: '-'; ?></td>
                                        <td><?php echo $fichaje['hora_salida'] ?: '-'; ?></td>
                                        <td><?php echo $fichaje['proyecto_nombre'] ?? '-'; ?></td>
                                        <td>
                                            <?php if ($fichaje['tarde']): ?>
                                            <span class="badge badge-estado-tarde">Tarde</span>
                                            <?php elseif ($fichaje['hora_salida']): ?>
                                            <span class="badge badge-estado-aprobada">Completo</span>
                                            <?php else: ?>
                                            <span class="badge badge-estado-pendiente">Pendiente</span>
                                            <?php endif; ?>
                                            <?php if ($fichaje['telework']): ?>
                                            <span class="badge badge-estado-teletrabajo">TW</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

<script>
    // Actualizar reloj cada segundo
    setInterval(function() {
        var now = new Date();
        var horas = now.getHours().toString().padStart(2, '0');
        var minutos = now.getMinutes().toString().padStart(2, '0');
        var segundos = now.getSeconds().toString().padStart(2, '0');
        document.getElementById('current-time').textContent = horas + ':' + minutos + ':' + segundos;
        
        var opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('current-date').textContent = now.toLocaleDateString('es-ES', opciones);
    }, 1000);
</script>

</body>
</html>