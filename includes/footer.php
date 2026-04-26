<?php
/**
 * MONCAO SECURE - Footer
 * Pie de página
 */
?>
</main> <!-- Cerrar el main si está abierto -->
</div> <!-- Cerrar el container-fluid si está abierto -->
</div> <!-- Cerrar el row si está abierto -->

<footer class="bg-white py-4 mt-auto shadow-sm">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <p class="text-muted mb-0">
                    <i class="fas fa-lock me-1"></i> 
                    <strong>MONCAO SECURE</strong> - Control de Acceso y Fichaje
                </p>
                <p class="text-muted small mb-0">
                    &copy; <?php echo date('Y'); ?> Roses, España
                </p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <p class="text-muted small mb-0">
                    ¿Necesitas ayuda? 
                    <a href="mailto:soporte@moncao.com" class="text-decoration-none">soporte@moncao.com</a>
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5.3 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<!-- Custom JS -->
<script src="assets/js/main.js"></script>

<?php if (isset($extraJS)): ?>
<?php echo $extraJS; ?>
<?php endif; ?>

</body>
</html>