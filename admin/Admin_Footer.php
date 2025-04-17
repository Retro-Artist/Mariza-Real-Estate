</div><!-- End of admin-content -->
    </main><!-- End of admin-main -->
</div><!-- End of admin-container -->

<!-- Check for alert messages from session and display them -->
<?php if (isset($_SESSION['alert_message']) && !empty($_SESSION['alert_message'])): ?>
    <div class="php-alert-message" 
         data-message="<?= htmlspecialchars($_SESSION['alert_message']) ?>" 
         data-type="<?= htmlspecialchars($_SESSION['alert_type'] ?? 'info') ?>"></div>
    <?php 
    // Clear the message after displaying
    unset($_SESSION['alert_message']); 
    unset($_SESSION['alert_type']);
    ?>
<?php endif; ?>

<!-- Scripts -->
<script src="<?= BASE_URL ?>/assets/scripts/alert-notification.js"></script>
<script src="<?= BASE_URL ?>/assets/scripts/admin-sidebar.js"></script>
<script src="<?= BASE_URL ?>/assets/scripts/script_loader.js"></script>
</body>
</html>

<?php ob_end_flush(); ?>