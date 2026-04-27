<?php
/**
 * MONCAO SECURE - Dashboard
 * Panel principal tras login
 */

session_start();

// Verificar autenticación
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'config/db.php';
require_once 'includes/auth_check.php';

$pageTitle = 'Dashboard';
$userId = $_SESSION['user_id'];
$userName = $_SESSION['nombre'];
$userRol = $_SESSION['rol'];
$departamentoId = $_SESSION['departamento_id'];

// Obtener datos para el dashboard
try {
    $pdo = getDB();
    
    // Fichaje de hoy
    $stmt = $pdo->prepare('SELECT * FROM fichajes WHERE user_id = ? AND fecha = CURDATE()');
    $stmt->execute([$userId]);
    $fichajeHoy = $stmt->fetch();
    
    // Proyectos asignados al usuario
    $stmt = $pdo->prepare('SELECT p.* FROM proyectos p 
                          JOIN proyecto_usuario pu ON p.id = pu.proyecto_id 
                          WHERE pu.user_id = ? AND p.activo = TRUE');
    $stmt->execute([$userId]);
    $proyectos = $stmt->fetchAll();
    
    // Horas trabajadas esta semana (Lunes a Viernes)
    $stmt = $pdo->prepare('SELECT SUM(TIMESTAMPDIFF(HOUR, hora_entrada, hora_salida)) as horas 
                          FROM fichajes 
                          WHERE user_id = ? 
                          AND YEARWEEK(fecha, 1) = YEARWEEK(CURDATE(), 1)
                          AND hora_salida IS NOT NULL');
    $stmt->execute([$userId]);
    $horasSemana = $stmt->fetch()['horas'] ?? 0;
    
    // Solicitudes pendientes
    $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM solicitudes WHERE user_id = ? AND estado = "pendiente"');
    $stmt->execute([$userId]);
    $solicitudesPendientes = $stmt->fetch()['total'] ?? 0;
    
    // Nombre del departamento
    if ($departamentoId) {
        $stmt = $pdo->prepare('SELECT nombre FROM departamentos WHERE id = ?');
        $stmt->execute([$departamentoId]);
        $departamentoNombre = $stmt->fetch()['nombre'] ?? 'Sin departamento';
    } else {
        $departamentoNombre = 'Sin departamento';
    }
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Error al cargar los datos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MONCAO SECURE - Control de Acceso y Fichaje">
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
    <link href="assets/css/style.css" rel="stylesheet">
    
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
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--color-secondary);
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main>
    <div class="container-fluid py-4">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h2 class="mb-1">
                            <i class="fas fa-user-circle me-2 text-primary"></i>
                            Bienvenido, <?php echo htmlspecialchars($userName); ?>!
                        </h2>
                        <p class="text-muted mb-0">
                            <?php echo ucfirst($userRol); ?> - <?php echo htmlspecialchars($departamentoNombre); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stats Row -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon me-3">
                            <i class="fas fa-clock fa-2x text-primary"></i>
                        </div>
                        <div>
                            <div class="stats-value"><?php echo $fichajeHoy ? 'Fichado' : 'No fichado'; ?></div>
                            <div class="stats-label">Estado de hoy</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon me-3">
                            <i class="fas fa-hourglass-half fa-2x text-primary"></i>
                        </div>
                        <div>
                            <div class="stats-value"><?php echo number_format($horasSemana, 1); ?>h</div>
                            <div class="stats-label">Horas esta semana</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon me-3">
                            <i class="fas fa-project-diagram fa-2x text-primary"></i>
                        </div>
                        <div>
                            <div class="stats-value"><?php echo count($proyectos); ?></div>
                            <div class="stats-label">Proyectos activos</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon me-3">
                            <i class="fas fa-file-alt fa-2x text-warning"></i>
                        </div>
                        <div>
                            <div class="stats-value"><?php echo $solicitudesPendientes; ?></div>
                            <div class="stats-label">Solicitudes pendientes</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Access Cards -->
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="mb-3"><i class="fas fa-th-large me-2"></i>Acceso Rápido</h4>
            </div>
        </div>
        
        <div class="row g-3 mb-4">
            <!-- Fichaje -->
            <div class="col-md-4 col-lg-2">
                <a href="modules/fichaje.php" class="dashboard-card">
                    <div class="card">
                        <div class="card-body">
                            <i class="fas fa-clock card-icon"></i>
                            <h6 class="card-title">Fichaje</h6>
                        </div>
                    </div>
                </a>
            </div>
            
            <!-- Horario -->
            <div class="col-md-4 col-lg-2">
                <a href="modules/horario.php" class="dashboard-card">
                    <div class="card">
                        <div class="card-body">
                            <i class="fas fa-calendar card-icon"></i>
                            <h6 class="card-title">Horario</h6>
                        </div>
                    </div>
                </a>
            </div>
            
            <!-- Solicitudes -->
            <div class="col-md-4 col-lg-2">
                <a href="modules/solicitudes.php" class="dashboard-card">
                    <div class="card">
                        <div class="card-body">
                            <i class="fas fa-file-alt card-icon"></i>
                            <h6 class="card-title">Solicitudes</h6>
                        </div>
                    </div>
                </a>
            </div>
            
            <!-- Informes -->
            <div class="col-md-4 col-lg-2">
                <a href="modules/informes.php" class="dashboard-card">
                    <div class="card">
                        <div class="card-body">
                            <i class="fas fa-chart-bar card-icon"></i>
                            <h6 class="card-title">Informes</h6>
                        </div>
                    </div>
                </a>
            </div>
            
            <!-- Proyectos -->
            <div class="col-md-4 col-lg-2">
                <a href="modules/proyectos.php" class="dashboard-card">
                    <div class="card">
                        <div class="card-body">
                            <i class="fas fa-project-diagram card-icon"></i>
                            <h6 class="card-title">Proyectos</h6>
                        </div>
                    </div>
                </a>
            </div>
            
            <!-- Perfil -->
            <div class="col-md-4 col-lg-2">
                <a href="modules/perfil.php" class="dashboard-card">
                    <div class="card">
                        <div class="card-body">
                            <i class="fas fa-user card-icon"></i>
                            <h6 class="card-title">Perfil</h6>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        
        <!-- Panel de Gestión (solo admin/superadmin) -->
        <?php if (isAdmin()): ?>
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="mb-3"><i class="fas fa-cogs me-2"></i>Panel de Gestión</h4>
            </div>
        </div>
        
        <div class="row g-3 mb-4">
            <!-- Gestión de Empleados -->
            <div class="col-md-4 col-lg-3">
                <a href="admin/empleados.php" class="dashboard-card">
                    <div class="card">
                        <div class="card-body">
                            <i class="fas fa-users card-icon"></i>
                            <h6 class="card-title">Empleados</h6>
                        </div>
                    </div>
                </a>
            </div>
            
            <!-- Gestión de Proyectos -->
            <div class="col-md-4 col-lg-3">
                <a href="admin/proyectos.php" class="dashboard-card">
                    <div class="card">
                        <div class="card-body">
                            <i class="fas fa-project-diagram card-icon"></i>
                            <h6 class="card-title">Proyectos</h6>
                        </div>
                    </div>
                </a>
            </div>
            
            <!-- Gestión de Solicitudes -->
            <div class="col-md-4 col-lg-3">
                <a href="admin/solicitudes.php" class="dashboard-card">
                    <div class="card">
                        <div class="card-body">
                            <i class="fas fa-check-circle card-icon"></i>
                            <h6 class="card-title">Solicitudes</h6>
                        </div>
                    </div>
                </a>
            </div>
            
            <!-- Gestión de Fichajes (solo RRHH y superadmin) -->
            <?php if ($departamentoId == 2 || isSuperadmin()): ?>
            <div class="col-md-4 col-lg-3">
                <a href="admin/fichajes.php" class="dashboard-card">
                    <div class="card">
                        <div class="card-body">
                            <i class="fas fa-edit card-icon"></i>
                            <h6 class="card-title">Fichajes</h6>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Proyectos Asignados -->
        <?php if (!empty($proyectos)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-project-diagram me-2"></i>Proyectos Asignados
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Proyecto</th>
                                        <th>Fecha Inicio</th>
                                        <th>Fecha Fin</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($proyectos as $proyecto): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($proyecto['nombre']); ?></td>
                                        <td><?php echo $proyecto['fecha_inicio'] ? date('d/m/Y', strtotime($proyecto['fecha_inicio'])) : '-'; ?></td>
                                        <td><?php echo $proyecto['fecha_fin'] ? date('d/m/Y', strtotime($proyecto['fecha_fin'])) : '-'; ?></td>
                                        <td>
                                            <?php if ($proyecto['activo']): ?>
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
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>