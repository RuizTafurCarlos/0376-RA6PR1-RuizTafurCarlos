<?php
/**
 * MONCAO SECURE - Proyectos
 * Ver proyectos asignados al usuario
 */

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/auth_check.php';

$pageTitle = 'Proyectos';
$userId = $_SESSION['user_id'];
$proyectos = [];
$statsProyectos = [];

try {
    $pdo = getDB();
    
    // Obtener proyectos asignados al usuario
    $stmt = $pdo->prepare('SELECT p.*, d.nombre as depto_nombre 
                          FROM proyectos p 
                          LEFT JOIN departamentos d ON p.departamento_id = d.id
                          JOIN proyecto_usuario pu ON p.id = pu.proyecto_id
                          WHERE pu.user_id = ? 
                          ORDER BY p.activo DESC, p.fecha_inicio DESC');
    $stmt->execute([$userId]);
    $proyectos = $stmt->fetchAll();
    
    // Estadísticas de fichajes por proyecto
    $stmt = $pdo->prepare('SELECT proyecto_id, COUNT(*) as dias, SUM(TIMESTAMPDIFF(HOUR, hora_entrada, hora_salida)) as horas
                          FROM fichajes 
                          WHERE user_id = ? AND hora_salida IS NOT NULL
                          GROUP BY proyecto_id');
    $stmt->execute([$userId]);
    $statsProyectos = [];
    while ($row = $stmt->fetch()) {
        $statsProyectos[$row['proyecto_id']] = $row;
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
        <div class="row mb-4">
            <div class="col-12">
                <h2><i class="fas fa-project-diagram me-2"></i>Mis Proyectos</h2>
            </div>
        </div>
        
        <?php if (empty($proyectos)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No tienes proyectos asignados actualmente.
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($proyectos as $proyecto): 
                $stats = $statsProyectos[$proyecto['id']] ?? null;
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-folder me-2"></i><?php echo htmlspecialchars($proyecto['nombre']); ?></span>
                        <?php if ($proyecto['activo']): ?>
                        <span class="badge bg-success">Activo</span>
                        <?php else: ?>
                        <span class="badge bg-secondary">Finalizado</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr>
                                <th>Departamento:</th>
                                <td><?php echo htmlspecialchars($proyecto['depto_nombre'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Fecha Inicio:</th>
                                <td><?php echo $proyecto['fecha_inicio'] ? date('d/m/Y', strtotime($proyecto['fecha_inicio'])) : '-'; ?></td>
                            </tr>
                            <tr>
                                <th>Fecha Fin:</th>
                                <td><?php echo $proyecto['fecha_fin'] ? date('d/m/Y', strtotime($proyecto['fecha_fin'])) : '-'; ?></td>
                            </tr>
                            <?php if ($stats): ?>
                            <tr>
                                <th>Días trabajados:</th>
                                <td><?php echo $stats['dias']; ?></td>
                            </tr>
                            <tr>
                                <th>Horas totales:</th>
                                <td><?php echo number_format($stats['horas'], 1); ?>h</td>
                            </tr>
                            <?php else: ?>
                            <tr>
                                <th>Días trabajados:</th>
                                <td>0</td>
                            </tr>
                            <tr>
                                <th>Horas totales:</th>
                                <td>0h</td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

</body>
</html>
