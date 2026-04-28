<?php
/**
 * MONCAO SECURE - Admin Empleados
 * Gestión de empleados (admin/superadmin)
 */

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

require_once '../includes/auth_check.php';
requireRole(['admin', 'superadmin']);

require_once '../config/db.php';

$pageTitle = 'Gestión de Empleados';
$userId = $_SESSION['user_id'];
$departamentoId = $_SESSION['departamento_id'] ?? null;
$message = '';
$messageType = '';
$empleados = [];
$departamentos = [];

try {
    $pdo = getDB();
    
    // Obtener empleados
    if (isSuperadmin()) {
        $stmt = $pdo->query('SELECT u.*, d.nombre as depto_nombre FROM users u LEFT JOIN departamentos d ON u.departamento_id = d.id ORDER BY u.nombre');
    } else {
        $stmt = $pdo->prepare('SELECT u.*, d.nombre as depto_nombre FROM users u LEFT JOIN departamentos d ON u.departamento_id = d.id WHERE u.departamento_id = ? ORDER BY u.nombre');
        $stmt->execute([$departamentoId]);
    }
    $empleados = $stmt->fetchAll();
    
    // Obtener departamentos
    $stmt = $pdo->query('SELECT * FROM departamentos ORDER BY nombre');
    $departamentos = $stmt->fetchAll();
    
    // Añadir empleado
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'añadir') {
        $nombre = $_POST['nombre'] ?? '';
        $email = $_POST['email'] ?? '';
        $rol = $_POST['rol'] ?? 'empleado';
        $deptoId = $_POST['departamento_id'] ?? null;
        
        if (empty($nombre) || empty($email)) {
            $message = 'El nombre y email son obligatorios.';
            $messageType = 'danger';
        } else {
            // Contraseña por defecto: moncao2024
            $password = password_hash('moncao2024', PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare('INSERT INTO users (nombre, email, password, rol, departamento_id) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$nombre, $email, $password, $rol, $deptoId]);
            
            $message = 'Empleado añadido correctamente. Contraseña: moncao2024';
            $messageType = 'success';
            
            // Recargar empleados
            if (isSuperadmin()) {
                $stmt = $pdo->query('SELECT u.*, d.nombre as depto_nombre FROM users u LEFT JOIN departamentos d ON u.departamento_id = d.id ORDER BY u.nombre');
            } else {
                $stmt = $pdo->prepare('SELECT u.*, d.nombre as depto_nombre FROM users u LEFT JOIN departamentos d ON u.departamento_id = d.id WHERE u.departamento_id = ? ORDER BY u.nombre');
                $stmt->execute([$departamentoId]);
            }
            $empleados = $stmt->fetchAll();
        }
    }
    
    // Archivar empleado
    if (isset($_GET['archivar'])) {
        $stmt = $pdo->prepare('UPDATE users SET archivado = TRUE, activo = FALSE WHERE id = ?');
        $stmt->execute([$_GET['archivar']]);
        
        $message = 'Empleado archivado.';
        $messageType = 'success';
        
        // Recargar
        if (isSuperadmin()) {
            $stmt = $pdo->query('SELECT u.*, d.nombre as depto_nombre FROM users u LEFT JOIN departamentos d ON u.departamento_id = d.id ORDER BY u.nombre');
        } else {
            $stmt = $pdo->prepare('SELECT u.*, d.nombre as depto_nombre FROM users u LEFT JOIN departamentos d ON u.departamento_id = d.id WHERE u.departamento_id = ? ORDER BY u.nombre');
            $stmt->execute([$departamentoId]);
        }
        $empleados = $stmt->fetchAll();
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
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <style>
        :root { --color-primary: #1A73E8; --color-secondary: #F1F3F4; --color-dark: #202124; }
        body { font-family: 'Inter', sans-serif; background-color: var(--color-secondary); }
    </style>
</head>
<body>

<?php include '../includes/navbar.php'; ?>

<main>
    <div class="container-fluid py-4">
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Añadir Empleado -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-user-plus me-2"></i>Añadir Empleado
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" class="row g-3">
                            <input type="hidden" name="action" value="añadir">
                            
                            <div class="col-md-3">
                                <label for="nombre" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                            </div>
                            <div class="col-md-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="col-md-2">
                                <label for="rol" class="form-label">Rol</label>
                                <select class="form-select" id="rol" name="rol">
                                    <option value="empleado">Empleado</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="departamento_id" class="form-label">Departamento</label>
                                <select class="form-select" id="departamento_id" name="departamento_id">
                                    <option value="">Sin asignar</option>
                                    <?php foreach ($departamentos as $d): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-2"></i>Añadir
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Lista de Empleados -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-users me-2"></i>Empleados
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Departamento</th>
                                        <th>Rol</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($empleados as $emp): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($emp['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                        <td><?php echo htmlspecialchars($emp['depto_nombre'] ?? '-'); ?></td>
                                        <td><span class="badge bg-<?php echo $emp['rol'] === 'superadmin' ? 'dark' : ($emp['rol'] === 'admin' ? 'warning' : 'primary'); ?>"><?php echo ucfirst($emp['rol']); ?></span></td>
                                        <td>
                                            <?php if ($emp['activo']): ?>
                                            <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!$emp['archivado']): ?>
                                            <a href="?archivar=<?php echo $emp['id']; ?>" class="btn btn-sm btn-warning" onclick="return confirm('¿Archivar empleado?')">
                                                <i class="fas fa-archive"></i>
                                            </a>
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
    </div>
</main>

<?php include '../includes/footer.php'; ?>

</body>
</html>