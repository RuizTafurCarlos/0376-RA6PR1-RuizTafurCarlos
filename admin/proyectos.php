<?php
/**
 * MONCAO SECURE - Admin Proyectos
 * Gestión de proyectos (admin/superadmin)
 */

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

requireRole(['admin', 'superadmin']);

require_once '../config/db.php';

$pageTitle = 'Gestión de Proyectos';
$userId = $_SESSION['user_id'];
$departamentoId = $_SESSION['departamento_id'];
$message = '';
$messageType = '';

try {
    $pdo = getDB();
    
    // Obtener proyectos
    if (isSuperadmin()) {
        $stmt = $pdo->query('SELECT p.*, d.nombre as depto_nombre FROM proyectos p LEFT JOIN departamentos d ON p.departamento_id = d.id ORDER BY p.activo DESC, p.nombre');
    } else {
        $stmt = $pdo->prepare('SELECT p.*, d.nombre as depto_nombre FROM proyectos p LEFT JOIN departamentos d ON p.departamento_id = d.id WHERE p.departamento_id = ? ORDER BY p.activo DESC, p.nombre');
        $stmt->execute([$departamentoId]);
    }
    $proyectos = $stmt->fetchAll();
    
    // Obtener departamentos
    $stmt = $pdo->query('SELECT * FROM departamentos ORDER BY nombre');
    $departamentos = $stmt->fetchAll();
    
    // Obtener empleados disponibles
    if (isSuperadmin()) {
        $stmt = $pdo->query('SELECT id, nombre FROM users WHERE archivado = FALSE ORDER BY nombre');
    } else {
        $stmt = $pdo->prepare('SELECT id, nombre FROM users WHERE departamento_id = ? AND archivado = FALSE ORDER BY nombre');
        $stmt->execute([$departamentoId]);
    }
    $empleados = $stmt->fetchAll();
    
    // Crear proyecto
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear') {
        $nombre = $_POST['nombre'] ?? '';
        $deptoId = $_POST['departamento_id'] ?? null;
        $fechaInicio = $_POST['fecha_inicio'] ?? null;
        $fechaFin = $_POST['fecha_fin'] ?? null;
        
        if (empty($nombre)) {
            $message = 'El nombre del proyecto es obligatorio.';
            $messageType = 'danger';
        } else {
            $stmt = $pdo->prepare('INSERT INTO proyectos (nombre, departamento_id, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?)');
            $stmt->execute([$nombre, $deptoId, $fechaInicio, $fechaFin]);
            $proyectoId = $pdo->lastInsertId();
            
            // Asignar empleados si se seleccionaron
            if (isset($_POST['empleados']) && is_array($_POST['empleados'])) {
                $stmt = $pdo->prepare('INSERT INTO proyecto_usuario (proyecto_id, user_id) VALUES (?, ?)');
                foreach ($_POST['empleados'] as $empId) {
                    $stmt->execute([$proyectoId, $empId]);
                }
            }
            
            $message = 'Proyecto creado correctamente.';
            $messageType = 'success';
            
            // Recargar proyectos
            if (isSuperadmin()) {
                $stmt = $pdo->query('SELECT p.*, d.nombre as depto_nombre FROM proyectos p LEFT JOIN departamentos d ON p.departamento_id = d.id ORDER BY p.activo DESC, p.nombre');
            } else {
                $stmt = $pdo->prepare('SELECT p.*, d.nombre as depto_nombre FROM proyectos p LEFT JOIN departamentos d ON p.departamento_id = d.id WHERE p.departamento_id = ? ORDER BY p.activo DESC, p.nombre');
                $stmt->execute([$departamentoId]);
            }
            $proyectos = $stmt->fetchAll();
        }
    }
    
    // Alternar estado (activo/inactivo)
    if (isset($_GET['toggle'])) {
        $proyectoId = $_GET['toggle'];
        $stmt = $pdo->prepare('UPDATE proyectos SET activo = NOT activo WHERE id = ?');
        $stmt->execute([$proyectoId]);
        
        $message = 'Estado del proyecto actualizado.';
        $messageType = 'success';
        
        // Recargar
        if (isSuperadmin()) {
            $stmt = $pdo->query('SELECT p.*, d.nombre as depto_nombre FROM proyectos p LEFT JOIN departamentos d ON p.departamento_id = d.id ORDER BY p.activo DESC, p.nombre');
        } else {
            $stmt = $pdo->prepare('SELECT p.*, d.nombre as depto_nombre FROM proyectos p LEFT JOIN departamentos d ON p.departamento_id = d.id WHERE p.departamento_id = ? ORDER BY p.activo DESC, p.nombre');
            $stmt->execute([$departamentoId]);
        }
        $proyectos = $stmt->fetchAll();
    }
    
    // Asignar/Desasignar empleado
    if (isset($_GET['asignar']) && isset($_GET['proyecto'])) {
        $stmt = $pdo->prepare('INSERT INTO proyecto_usuario (proyecto_id, user_id) VALUES (?, ?)');
        $stmt->execute([$_GET['proyecto'], $_GET['asignar']]);
        header('Location: proyectos.php?msg=asignado');
        exit;
    }
    
    if (isset($_GET['desasignar']) && isset($_GET['proyecto'])) {
        $stmt = $pdo->prepare('DELETE FROM proyecto_usuario WHERE proyecto_id = ? AND user_id = ?');
        $stmt->execute([$_GET['proyecto'], $_GET['desasignar']]);
        header('Location: proyectos.php?msg=desasignado');
        exit;
    }
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    $message = 'Error al procesar.';
    $messageType = 'danger';
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
        
        <!-- Crear Proyecto -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-plus me-2"></i>Crear Proyecto
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" class="row g-3">
                            <input type="hidden" name="action" value="crear">
                            
                            <div class="col-md-3">
                                <label for="nombre" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                            </div>
                            <div class="col-md-3">
                                <label for="departamento_id" class="form-label">Departamento</label>
                                <select class="form-select" id="departamento_id" name="departamento_id">
                                    <option value="">General</option>
                                    <?php foreach ($departamentos as $d): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio">
                            </div>
                            <div class="col-md-2">
                                <label for="fecha_fin" class="form-label">Fecha Fin</label>
                                <input type="date" class="form-control" id="fecha_fin" name="fecha_fin">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-2"></i>Crear
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Lista de Proyectos -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-project-diagram me-2"></i>Proyectos
                    </div>
                    <div class="card-body">
                        <?php if (empty($proyectos)): ?>
                        <p class="text-muted text-center mb-0">No hay proyectos</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Departamento</th>
                                        <th>Fecha Inicio</th>
                                        <th>Fecha Fin</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($proyectos as $p): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($p['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($p['depto_nombre'] ?? '-'); ?></td>
                                        <td><?php echo $p['fecha_inicio'] ? date('d/m/Y', strtotime($p['fecha_inicio'])) : '-'; ?></td>
                                        <td><?php echo $p['fecha_fin'] ? date('d/m/Y', strtotime($p['fecha_fin'])) : '-'; ?></td>
                                        <td>
                                            <?php if ($p['activo']): ?>
                                            <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?toggle=<?php echo $p['id']; ?>" class="btn btn-sm btn-warning" onclick="return confirm('¿Cambiar estado?')">
                                                <i class="fas fa-toggle-on"></i>
                                            </a>
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

</body>
</html>
