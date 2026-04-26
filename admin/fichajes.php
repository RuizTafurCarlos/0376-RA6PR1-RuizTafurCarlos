<?php
/**
 * MONCAO SECURE - Admin Fichajes
 * Modificar fichajes (solo RRHH y superadmin)
 */

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// Solo RRHH (departamento 2) o superadmin
requireRole('superadmin');
if ($_SESSION['departamento_id'] != 2 && $_SESSION['rol'] != 'superadmin') {
    header('Location: ../dashboard.php');
    exit;
}

require_once '../config/db.php';

$pageTitle = 'Gestión de Fichajes';
$departamentoId = $_SESSION['departamento_id'];

try {
    $pdo = getDB();
    
    // Buscar por empleado y fecha
    $fichajes = [];
    if (isset($_GET['buscar'])) {
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        
        if (isSuperadmin()) {
            $stmt = $pdo->prepare('SELECT f.*, u.nombre as usuario_nombre FROM fichajes f JOIN users u ON f.user_id = u.id WHERE f.fecha = ? ORDER BY u.nombre');
            $stmt->execute([$fecha]);
        } else {
            $stmt = $pdo->prepare('SELECT f.*, u.nombre as usuario_nombre FROM fichajes f JOIN users u ON f.user_id = u.id WHERE f.fecha = ? AND u.departamento_id = ? ORDER BY u.nombre');
            $stmt->execute([$fecha, $departamentoId]);
        }
        $fichajes = $stmt->fetchAll();
    }
    
    // Actualizar fichaje
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'actualizar') {
        $fichajeId = $_POST['fichaje_id'];
        $horaEntrada = $_POST['hora_entrada'];
        $horaSalida = $_POST['hora_salida'];
        
        // Calcular retraso y horas extra
        $tarde = $horaEntrada > '09:05:00';
        $minutosRetraso = $tarde ? (strtotime($horaEntrada) - strtotime('09:00:00')) / 60 : 0;
        $horasExtra = $horaSalida && $horaSalida > '17:00:00' ? (strtotime($horaSalida) - strtotime('17:00:00')) / 3600 : 0;
        
        $stmt = $pdo->prepare('UPDATE fichajes SET hora_entrada = ?, hora_salida = ?, tarde = ?, minutos_retraso = ?, horas_extra = ? WHERE id = ?');
        $stmt->execute([$horaEntrada, $horaSalida, $tarde ? 1 : 0, $minutosRetraso, $horasExtra, $fichajeId]);
        
        $message = 'Fichaje actualizado.';
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
<div class="card-header"><i class="fas fa-search me-2"></i>Buscar Fichajes</div>
<div class="card-body">
<form method="GET" action="" class="row g-3">
<div class="col-md-4"><label class="form-label">Fecha</label><input type="date" class="form-control" name="fecha" value="<?php echo $_GET['fecha'] ?? date('Y-m-d'); ?>"></div>
<div class="col-md-2"><label class="form-label">&nbsp;</label><button type="submit" name="buscar" class="btn btn-primary w-100"><i class="fas fa-search"></i> Buscar</button></div>
</form>
</div>
</div>
</div>
</div>
<?php if(!empty($fichajes)){echo '<div class="row"><div class="col-12"><div class="card"><div class="card-header"><i class="fas fa-clock me-2"></i>Fichajes del Día</div><div class="card-body"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Usuario</th><th>Entrada</th><th>Salida</th><th>Tarde</th><th>Minutos</th><th>Extra</th><th>Acciones</th></tr></thead><tbody>';foreach($fichajes as $f){echo '<tr><form method="POST" action=""><td>'.htmlspecialchars($f['usuario_nombre']).'</td><td><input type="time" class="form-control form-control-sm" name="hora_entrada" value="'.$f['hora_entrada'].'"></td><td><input type="time" class="form-control form-control-sm" name="hora_salida" value="'.$f['hora_salida'].'"></td><td>'.($f['tarde']?'<span class="badge bg-danger">Sí</span>':'<span class="badge bg-success">No</span>').'</td><td>'.$f['minutos_retraso'].'</td><td>'.number_format($f['horas_extra'],2).'</td><td><input type="hidden" name="fichaje_id" value="'.$f['id'].'"><input type="hidden" name="action" value="actualizar"><button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i></button></td></form></tr>';}echo '</tbody></table></div></div></div></div>';}?>
</div>
</main>
<?php include '../includes/footer.php'; ?>
</body>
</html>