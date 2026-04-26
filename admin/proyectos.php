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
    
    // Obtener empleados para asignar
    $stmt = $pdo->prepare('SELECT id, nombre FROM users WHERE activo = TRUE AND archivado = FALSE AND departamento_id = ? ORDER BY nombre');
    $stmt->execute([$departamentoId]);
    $empleados = $stmt->fetchAll();
    
    // Crear proyecto
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear') {
        $nombre = $_POST['nombre'] ?? '';
        $deptoId = $_POST['departamento_id'] ?? $departamentoId;
        $fechaInicio = $_POST['fecha_inicio'] ?? null;
        $fechaFin = $_POST['fecha_fin'] ?? null;
        
        if (empty($nombre)) {
            $message = 'El nombre es obligatorio.';
            $messageType = 'danger';
        } else {
            $stmt = $pdo->prepare('INSERT INTO proyectos (nombre, departamento_id, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?)');
            $stmt->execute([$nombre, $deptoId, $fechaInicio, $fechaFin]);
            
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
    
    // Asignar empleado
    if (isset($_GET['asignar']) && isset($_GET['proyecto'])) {
        $stmt = $pdo->prepare('INSERT IGNORE INTO proyecto_usuario (proyecto_id, user_id) VALUES (?, ?)');
        $stmt->execute([$_GET['proyecto'], $_GET['asignar']]);
        $message = 'Empleado asignado.';
        $messageType = 'success';
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>:root{--color-primary:#1A73E8;--color-secondary:#F1F3F4}body{font-family:'Inter',sans-serif;background-color:var(--color-secondary)}</style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<main>
<div class="container-fluid py-4">
<?php if(!empty($message)){echo '<div class="alert alert-'.$messageType.' alert-dismissible fade show" role="alert">'.htmlspecialchars($message).'<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';}?>
<div class="row mb-4">
<div class="col-12">
<div class="card">
<div class="card-header"><i class="fas fa-plus me-2"></i>Crear Proyecto</div>
<div class="card-body">
<form method="POST" action="" class="row g-3">
<input type="hidden" name="action" value="crear">
<div class="col-md-4"><label class="form-label">Nombre</label><input type="text" class="form-control" name="nombre" required></div>
<div class="col-md-3"><label class="form-label">Departamento</label><select class="form-select" name="departamento_id"><?php foreach($departamentos as $d){echo '<option value="'.$d['id'].'">'.htmlspecialchars($d['nombre']).'</option>';}?></select></div>
<div class="col-md-2"><label class="form-label">Fecha Inicio</label><input type="date" class="form-control" name="fecha_inicio"></div>
<div class="col-md-2"><label class="form-label">Fecha Fin</label><input type="date" class="form-control" name="fecha_fin"></div>
<div class="col-md-1"><label class="form-label">&nbsp;</label><button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus"></i></button></div>
</form>
</div>
</div>
</div>
</div>
<div class="row">
<div class="col-12">
<div class="card">
<div class="card-header"><i class="fas fa-project-diagram me-2"></i>Proyectos</div>
<div class="card-body">
<div class="table-responsive">
<table class="table table-hover mb-0">
<thead><tr><th>Proyecto</th><th>Departamento</th><th>Inicio</th><th>Fin</th><th>Estado</th><th>Acciones</th></tr></thead>
<tbody>
<?php foreach($proyectos as $p){echo '<tr><td>'.htmlspecialchars($p['nombre']).'</td><td>'.htmlspecialchars($p['depto_nombre']??'-').'</td><td>'.($p['fecha_inicio']?date('d/m/Y',strtotime($p['fecha_inicio'])):'-').'</td><td>'.($p['fecha_fin']?date('d/m/Y',strtotime($p['fecha_fin'])):'-').'</td><td>'.($p['activo']?'<span class="badge bg-success">Activo</span>':'<span class="badge bg-secondary">Finalizado</span>').'</td><td><a href="?proyecto='.$p['id'].'" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#asignarModal"><i class="fas fa-user-plus"></i></a></td></tr>';}?>
</tbody>
</table>
</div>
</div>
</div>
</div>
</div>
</main>
<?php include '../includes/footer.php'; ?>
</body>
</html>