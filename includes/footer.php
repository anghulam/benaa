        </main>

        <footer class="app-footer no-print">
            &copy; <?= date('Y') ?> <?= e(APP_NAME) ?> — جميع الحقوق محفوظة | الإصدار <?= e(APP_VERSION) ?>
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<?php if (!empty($extraScripts)) echo $extraScripts; ?>
</body>
</html>
