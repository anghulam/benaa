        </main>

        <footer class="app-footer no-print">
            &copy; <?= date('Y') ?> <?= e(appName()) ?> — جميع الحقوق محفوظة | الإصدار <?= e(APP_VERSION) ?>
        </footer>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<?php if (!empty($extraScripts)) echo $extraScripts; ?>
</body>
</html>
