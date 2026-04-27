<?php
/**
 * MONCAO SECURE - Navbar
 * Barra de navegación
 */

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$nombre = isset($_SESSION['nombre']) ? htmlspecialchars($_SESSION['nombre']) : 'Usuario';
$rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';
?>
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--color-primary);">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="dashboard.php">
            <i class="fas fa-lock me-2"></i>MONCAO SECURE
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="fas fa-home me-1"></i> Inicio
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'fichaje' ? 'active' : ''; ?>" href="modules/fichaje.php">
                        <i class="fas fa-clock me-1"></i> Fichaje
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'horario' ? 'active' : ''; ?>" href="modules/horario.php">
                        <i class="fas fa-calendar me-1"></i> Horario
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'solicitudes' ? 'active' : ''; ?>" href="modules/solicitudes.php">
                        <i class="fas fa-file-alt me-1"></i> Solicitudes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'informes' ? 'active' : ''; ?>" href="modules/informes.php">
                        <i class="fas fa-chart-bar me-1"></i> Informes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'proyectos' ? 'active' : ''; ?>" href="modules/proyectos.php">
                        <i class="fas fa-project-diagram me-1"></i> Proyectos
                    </a>
                </li>
                
                <?php if (isAdmin()): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-cogs me-1"></i> Gestión
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                        <li><a class="dropdown-item" href="admin/empleados.php"><i class="fas fa-users me-2"></i>Empleados</a></li>
                        <li><a class="dropdown-item" href="admin/proyectos.php"><i class="fas fa-project-diagram me-2"></i>Proyectos</a></li>
                        <li><a class="dropdown-item" href="admin/solicitudes.php"><i class="fas fa-check-circle me-2"></i>Solicitudes</a></li>
                        <?php if ($_SESSION['departamento_id'] == 2 || isSuperadmin()): ?>
                        <li><a class="dropdown-item" href="admin/fichajes.php"><i class="fas fa-edit me-2"></i>Fichajes</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i> <?php echo $nombre; ?>
                        <span class="badge bg-light text-dark ms-1"><?php echo ucfirst($rol); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="modules/perfil.php"><i class="fas fa-user me-2"></i>Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>