<?php if (basename($_SERVER['PHP_SELF']) !== 'login.php'): ?>
    <footer class="py-4 mt-auto">
        <div class="container-fluid px-4 text-center">
            <small>&copy; <?php echo date('Y'); ?> Fresh Grocers. Admin Portal.</small>
        </div>
    </footer>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Main site JS -->
<script src="<?php echo isset($BASE_URL) ? $BASE_URL : '/fresh grocers/'; ?>assets/js/script.js"></script>
</body>
</html>
