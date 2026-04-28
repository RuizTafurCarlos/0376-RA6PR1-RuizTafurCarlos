<?php
/**
 * MONCAO SECURE - Admin Fichajes
 * Gestión de fichajes (solo RRHH y superadmin)
 */

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

require_once '../includes/auth_check.php';
requireRole(['admin', 'superadmin']);

// Verificar que sea RRHH (departamento 2) o superadmin
$departamentoId = $_SESSION['departamento_id'];
if ($departamentoId != 2 && $_SESSION['rol'] !== 'superadmin') {
    header('Location: ../dashboard.php');
    exit;
}

require_once '../config/db.php';

$pageTitle = 'Gestión de Fichajes';
$message = '';
$messageType = '';
$empleados = [];
$fichajes = [];
$fechaDesde = date('Y-m-01');
$fechaHasta = date('Y-m-d');
$usuarioFiltro = '';

try {
    $pdo = getDB();
    
    // Filtros
    $fechaDesde = $_GET['fecha_desde'] ?? date('Y-m-01');
    $fechaHasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
    $usuarioFiltro = $_GET['user_id'] ?? '';
    
    // Obtener empleados para filtro
    if (isSuperadmin()) {
        $stmt = $pdo->query('SELECT id, nombre FROM users WHERE archivado = FALSE ORDER BY nombre');
    } else {
        $stmt = $pdo->prepare('SELECT id, nombre FROM users WHERE departamento_id = ? AND archivado = FALSE ORDER BY nombre');
        $stmt->execute([$departamentoId]);
    }
    $empleados = $stmt->fetchAll();
    
    // Construir query de fichajes
    $query = 'SELECT f.*, u.nombre as usuario_nombre, p.nombre as proyecto_nombre
              FROM fichajes f
              JOIN users u ON f.user_id = u.id
              LEFT JOIN proyectos p ON f.proyecto_id = p.id
              WHERE f.fecha BETWEEN ? AND ?';
    $params = [$fechaDesde, $fechaHasta];
    
    if (!empty($usuarioFiltro)) {
        $query .= ' AND f.user_id = ?';
        $params[] = $usuarioFiltro;
    }
    
    if (!isSuperadmin()) {
        $query .= ' AND u.departamento_id = ?';
        $params[] = $departamentoId;
    }
    
    $query .= ' ORDER BY f.fecha DESC, u.nombre';
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $fichajes = $stmt->fetchAll();
    
    // Procesar corrección de fichaje
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'corregir') {
        $fichajeId = $_POST['fichaje_id'] ?? 0;
        $horaEntrada = $_POST['hora_entrada'] ?? null;
        $horaSalida = $_POST['hora_salida'] ?? null;
        
        if ($fichajeId) {
            $stmt = $pdo->prepare('UPDATE fichajes SET hora_entrada = ?, hora_salida = ? WHERE id = ?');
            $stmt->execute([$horaEntrada, $horaSalida, $fichajeId]);
            $message = 'Fichaje corregido correctamente.';
            $messageType = 'success';
            
            // Recargar fichajes
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $fichajes = $stmt->fetchAll();
        }
    }
    
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
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Filtros -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-filter me-2"></i>Filtros
                    </div>
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-3">
                                <label for="fecha_desde" class="form-label">Desde</label>
                                <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" value="<?php echo $fechaDesde; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="fecha_hasta" class="form-label">Hasta</label>
                                <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" value="<?php echo $fechaHasta; ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="user_id" class="form-label">Empleado</label>
                                <select class="form-select" id="user_id" name="user_id">
                                    <option value="">Todos</option>
                                    <?php foreach ($empleados as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>" <?php echo $usuarioFiltro == $emp['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($emp['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
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
        
        <!-- Lista de Fichajes -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-clock me-2"></i>Fichajes (<?php echo count($fichajes); ?>)
                    </div>
                    <div class="card-body">
                        <?php if (empty($fichajes)): ?>
                        <p class="text-muted text-center mb-0">No hay fichajes en este período</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Empleado</th>
                                        <th>Entrada</th>
                                        <th>Salida</th>
                                        <th>Proyecto</th>
                                        <th>Estado</th>
                                        <th>Horas Extra</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fichajes as $f): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($f['fecha'])); ?></td>
                                        <td><?php echo htmlspecialchars($f['usuario_nombre']); ?></td>
                                        <td><?php echo $f['hora_entrada'] ?: '-'; ?></td>
                                        <td><?php echo $f['hora_salida'] ?: '-'; ?></td>
                                        <td><?php echo htmlspecialchars($f['proyecto_nombre'] ?? '-'); ?></td>
                                        <td>
                                            <?php if ($f['tarde']): ?>
                                            <span class="badge bg-danger">Tarde (<?php echo $f['minutos_retraso']; ?> min)</span>
                                            <?php elseif ($f['telework']): ?>
                                            <span class="badge badge-estado-teletrabajo">TW</span>
                                            <?php elseif ($f['hora_salida']): ?>
                                            <span class="badge bg-success">OK</span>
                                            <?php else: ?>
                                            <span class="badge bg-warning">Pendiente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo number_format($f['horas_extra'], 2); ?>h</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalCorregir<?php echo $f['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Modal Corregir -->
                                    <div class="modal fade" id="modalCorregir<?php echo $f['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Corregir Fichaje</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" action="">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="corregir">
                                                        <input type="hidden" name="fichaje_id" value="<?php echo $f['id']; ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Hora Entrada</label>
                                                            <input type="time" class="form-control" name="hora_entrada" value="<?php echo $f['hora_entrada']; ?>">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Hora Salida</label>
                                                            <input type="time" class="form-control" name="hora_salida" value="<?php echo $f['hora_salida']; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-primary">Guardar</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
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

</body>
</html>
