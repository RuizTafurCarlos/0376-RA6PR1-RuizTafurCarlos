<?php
/**
 * MONCAO SECURE - Proyectos
 * Ver proyectos asignados
 */

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';

$pageTitle = 'Proyectos';
$userId = $_SESSION['user_id'];
$departamentoId = $_SESSION['departamento_id'];

try {
    $pdo = getDB();
    
    // Si es admin o superadmin, ver proyectos del departamento
    if (isAdmin()) {
        $stmt = $pdo->prepare('SELECT p.*, d.nombre as depto_nombre 
                            FROM proyectos p 
                            LEFT JOIN departamentos d ON p.departamento_id = d.id
                            WHERE p.departamento_id = ? OR ? = 1
                            ORDER BY p.activo DESC, p.nombre');
        $stmt->execute([$departamentoId, isSuperadmin() ? 1 : 0]);
    } else {
        // Empleado: solo proyectos asignados
        $stmt = $pdo->prepare('SELECT p.* FROM proyectos p 
                            JOIN proyecto_usuario pu ON p.id = pu.proyecto_id 
                            WHERE pu.user_id = ? AND p.activo = TRUE
                            ORDER BY p.nombre');
        $stmt->execute([$userId]);
    }
    $proyectos = $stmt->fetchAll();
    
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
                                        <th>Proyecto</th>
                                        <?php if (isAdmin()): ?>
                                        <th>Departamento</th>
                                        <?php endif; ?>
                                        <th>Fecha Inicio</th>
                                        <th>Fecha Fin</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($proyectos as $p): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($p['nombre']); ?></td>
                                        <?php if (isAdmin()): ?>
                                        <td><?php echo htmlspecialchars($p['depto_nombre'] ?? '-'); ?></td>
                                        <?php endif; ?>
                                        <td><?php echo $p['fecha_inicio'] ? date('d/m/Y', strtotime($p['fecha_inicio'])) : '-'; ?></td>
                                        <td><?php echo $p['fecha_fin'] ? date('d/m/Y', strtotime($p['fecha_fin'])) : '-'; ?></td>
                                        <td>
                                            <?php if ($p['activo']): ?>
                                            <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary">Finalizado</span>
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