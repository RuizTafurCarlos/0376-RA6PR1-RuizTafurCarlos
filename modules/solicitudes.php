<?php
/**
 * MONCAO SECURE - Solicitudes
 * Gestión de solicitudes
 */

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';

$pageTitle = 'Solicitudes';
$userId = $_SESSION['user_id'];
$message = '';
$messageType = '';

try {
    $pdo = getDB();
    
    // Obtener solicitudes del usuario
    $stmt = $pdo->prepare('SELECT * FROM solicitudes WHERE user_id = ? ORDER BY fecha DESC');
    $stmt->execute([$userId]);
    $solicitudes = $stmt->fetchAll();
    
    // Procesar nueva solicitud
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'nueva') {
        $tipo = $_POST['tipo'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        
        // Upload de archivo
        $justificanteUrl = null;
        if (isset($_FILES['justificante']) && $_FILES['justificante']['error'] === 0) {
            $ext = pathinfo($_FILES['justificante']['name'], PATHINFO_EXTENSION);
            if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'pdf'])) {
                $filename = uniqid() . '.' . $ext;
                $uploadDir = '../uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                move_uploaded_file($_FILES['justificante']['tmp_name'], $uploadDir . $filename);
                $justificanteUrl = $filename;
            }
        }
        
        $stmt = $pdo->prepare('INSERT INTO solicitudes (user_id, tipo, descripcion, justificante_url) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, $tipo, $descripcion, $justificanteUrl]);
        
        $message = 'Solicitud enviada correctamente.';
        $messageType = 'success';
        
        // Recargar solicitudes
        $stmt = $pdo->prepare('SELECT * FROM solicitudes WHERE user_id = ? ORDER BY fecha DESC');
        $stmt->execute([$userId]);
        $solicitudes = $stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    $message = 'Error al procesar la solicitud.';
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
        
        <!-- Nueva Solicitud -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-plus me-2"></i>Nueva Solicitud
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="nueva">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="tipo" class="form-label">Tipo de Solicitud</label>
                                    <select class="form-select" id="tipo" name="tipo" required>
                                        <option value="">Selecciona...</option>
                                        <option value="vacaciones">Vacaciones</option>
                                        <option value="baja_temporal">Baja Temporal</option>
                                        <option value="baja_voluntaria">Baja Voluntaria</option>
                                        <option value="cambio_horario">Cambio de Horario</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="justificante" class="form-label">Justificante (opcional)</label>
                                    <input type="file" class="form-control" id="justificante" name="justificante" accept=".jpg,.jpeg,.png,.pdf">
                                    <small class="text-muted">JPG, PNG o PDF (máx 5MB)</small>
                                </div>
                                <div class="col-12">
                                    <label for="descripcion" class="form-label">Descripción</label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Enviar Solicitud
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Historial -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-history me-2"></i>Mis Solicitudes
                    </div>
                    <div class="card-body">
                        <?php if (empty($solicitudes)): ?>
                        <p class="text-muted text-center mb-0">No hay solicitudes</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Descripción</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($solicitudes as $sol): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sol['tipo']); ?></td>
                                        <td><?php echo htmlspecialchars($sol['descripcion']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($sol['fecha'])); ?></td>
                                        <td>
                                            <?php if ($sol['estado'] === 'pendiente'): ?>
                                            <span class="badge badge-estado-pendiente">Pendiente</span>
                                            <?php elseif ($sol['estado'] === 'aprobada'): ?>
                                            <span class="badge badge-estado-aprobada">Aprobada</span>
                                            <?php else: ?>
                                            <span class="badge badge-estado-denegada">Denegada</span>
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

</body>
</html>