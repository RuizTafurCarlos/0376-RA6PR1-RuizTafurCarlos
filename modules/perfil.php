<?php
/**
 * MONCAO SECURE - Perfil
 * Perfil y cambio de contraseña
 */

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/auth_check.php';

$pageTitle = 'Perfil';
$userId = $_SESSION['user_id'];
$departamentoId = $_SESSION['departamento_id'] ?? null;
$message = '';
$messageType = '';
$user = null;

try {
    $pdo = getDB();
    
    // Obtener datos del usuario
    $stmt = $pdo->prepare('SELECT u.*, d.nombre as depto_nombre FROM users u LEFT JOIN departamentos d ON u.departamento_id = d.id WHERE u.id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    // Nombre del departamento
    if ($departamentoId) {
        $stmt = $pdo->prepare('SELECT nombre FROM departamentos WHERE id = ?');
        $stmt->execute([$departamentoId]);
        $depto = $stmt->fetch();
    }
    
    // Procesar cambio de contraseña
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cambiar_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $message = 'Todos los campos son obligatorios.';
            $messageType = 'danger';
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'Las contraseñas no coinciden.';
            $messageType = 'danger';
        } elseif (strlen($newPassword) < 6) {
            $message = 'La contraseña debe tener al menos 6 caracteres.';
            $messageType = 'danger';
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $message = 'La contraseña actual es incorrecta.';
            $messageType = 'danger';
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([$hash, $userId]);
            $message = 'Contraseña cambiada correctamente.';
            $messageType = 'success';
        }
    }
    
    // Procesar cambio de foto
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cambiar_foto') {
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif'])) {
                $filename = 'perfil_' . $userId . '.' . $ext;
                $uploadDir = '../assets/img/perfiles/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                move_uploaded_file($_FILES['foto']['tmp_name'], $uploadDir . $filename);
                
                $stmt = $pdo->prepare('UPDATE users SET foto_url = ? WHERE id = ?');
                $stmt->execute([$filename, $userId]);
                $message = 'Foto actualizada correctamente.';
                $messageType = 'success';
                
                // Recargar datos
                $stmt = $pdo->prepare('SELECT u.*, d.nombre as depto_nombre FROM users u LEFT JOIN departamentos d ON u.departamento_id = d.id WHERE u.id = ?');
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
            }
        }
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
        .profile-photo { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--color-primary); }
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
        
        <!-- Datos Personales -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-user me-2"></i>Foto de Perfil
                    </div>
                    <div class="card-body text-center">
                        <?php if (!empty($user['foto_url'])): ?>
                        <img src="../assets/img/perfiles/<?php echo htmlspecialchars($user['foto_url']); ?>" class="profile-photo mb-3" alt="Foto">
                        <?php else: ?>
                        <i class="fas fa-user fa-5x text-secondary mb-3"></i>
                        <?php endif; ?>
                        
                        <form method="POST" action="" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="cambiar_foto">
                            <input type="file" class="form-control mb-2" name="foto" accept=".jpg,.jpeg,.png,.gif">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload me-2"></i>Subir Foto
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-id-card me-2"></i>Datos Personales
                    </div>
                    <div class="card-body">
                        <table class="table mb-0">
                            <tr>
                                <th>Nombre:</th>
                                <td><?php echo htmlspecialchars($user['nombre']); ?></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                            </tr>
                            <tr>
                                <th>Departamento:</th>
                                <td><?php echo htmlspecialchars($user['depto_nombre'] ?? 'Sin asignar'); ?></td>
                            </tr>
                            <tr>
                                <th>Rol:</th>
                                <td><span class="badge bg-primary"><?php echo ucfirst($user['rol']); ?></span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Cambiar Contraseña -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-lock me-2"></i>Cambiar Contraseña
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" class="row g-3">
                            <input type="hidden" name="action" value="cambiar_password">
                            
                            <div class="col-md-4">
                                <label for="current_password" class="form-label">Contraseña Actual</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="col-md-4">
                                <label for="new_password" class="form-label">Nueva Contraseña</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            <div class="col-md-4">
                                <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Cambiar Contraseña
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

</body>
</html>