<?php
/**
 * MONCAO SECURE - Admin Solicitudes
 * Aprobar/Denegar solicitudes
 */

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

requireRole(['admin', 'superadmin']);

require_once '../config/db.php';

$pageTitle = 'Gestión de Solicitudes';
$userId = $_SESSION['user_id'];
$departamentoId = $_SESSION['departamento_id'];

try {
    $pdo = getDB();
    
    // Obtener solicitudes pendientes
    if (isSuperadmin()) {
        $stmt = $pdo->query('SELECT s.*, u.nombre as usuario_nombre FROM solicitudes s JOIN users u ON s.user_id = u.id WHERE s.estado = "pendiente" ORDER BY s.fecha DESC');
    } else {
        $stmt = $pdo->prepare('SELECT s.*, u.nombre as usuario_nombre FROM solicitudes s JOIN users u ON s.user_id = u.id WHERE s.estado = "pendiente" AND u.departamento_id = ? ORDER BY s.fecha DESC');
        $stmt->execute([$departamentoId]);
    }
    $solicitudes = $stmt->fetchAll();
    
    // Aprobar/Denegar
    if (isset($_GET['accion']) && isset($_GET['id'])) {
        $accion = $_GET['accion'];
        $solId = $_GET['id'];
        
        $stmt = $pdo->prepare('UPDATE solicitudes SET estado = ?, aprobado_por = ?, fecha_respuesta = NOW() WHERE id = ?');
        $stmt->execute([$accion, $userId, $solId]);
        
        // Si es baja voluntaria o despido, archivar usuario
        $stmt = $pdo->prepare('SELECT tipo, user_id FROM solicitudes WHERE id = ?');
        $stmt->execute([$solId]);
        $sol = $stmt->fetch();
        
        if (in_array($sol['tipo'], ['baja_voluntaria', 'despido']) && $accion === 'aprobada') {
            $stmt = $pdo->prepare('UPDATE users SET archivado = TRUE, activo = FALSE WHERE id = ?');
            $stmt->execute([$sol['user_id']]);
        }
        
        // Recargar
        if (isSuperadmin()) {
            $stmt = $pdo->query('SELECT s.*, u.nombre as usuario_nombre FROM solicitudes s JOIN users u ON s.user_id = u.id WHERE s.estado = "pendiente" ORDER BY s.fecha DESC');
        } else {
            $stmt = $pdo->prepare('SELECT s.*, u.nombre as usuario_nombre FROM solicitudes s JOIN users u ON s.user_id = u.id WHERE s.estado = "pendiente" AND u.departamento_id = ? ORDER BY s.fecha DESC');
            $stmt->execute([$departamentoId]);
        }
        $solicitudes = $stmt->fetchAll();
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
<div class="row">
<div class="col-12">
<div class="card">
<div class="card-header"><i class="fas fa-check-circle me-2"></i>Solicitudes Pendientes</div>
<div class="card-body">
<?php if(empty($solicitudes)){echo '<p class="text-muted text-center mb-0">No hay solicitudes pendientes</p>';}else{echo '<div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Usuario</th><th>Tipo</th><th>Descripción</th><th>Fecha</th><th>Acciones</th></tr></thead><tbody>';foreach($solicitudes as $s){echo '<tr><td>'.htmlspecialchars($s['usuario_nombre']).'</td><td>'.htmlspecialchars($s['tipo']).'</td><td>'.htmlspecialchars($s['descripcion']).'</td><td>'.date('d/m/Y',strtotime($s['fecha'])).'</td><td><a href="?accion=aprobada&id='.$s['id'].'" class="btn btn-sm btn-success me-1" onclick="return confirm(\'¿Aprobar?\')"><i class="fas fa-check"></i></a><a href="?accion=denegada&id='.$s['id'].'" class="btn btn-sm btn-danger" onclick="return confirm(\'¿Denegar?\')"><i class="fas fa-times"></i></a></td></tr>';}echo '</tbody></table></div>';}?>
</div>
</div>
</div>
</div>
</div>
</main>
<?php include '../includes/footer.php'; ?>
</body>
</html>