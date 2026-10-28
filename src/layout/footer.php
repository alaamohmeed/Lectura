<?php
/**
 * Layout Footer
 * Closes main, includes footer, bottom scripts
 */
?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="site-footer" role="contentinfo">
        <div class="container-fluid px-3 px-md-4 py-3">
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-2 text-muted small">
                <div class="text-center text-md-start">
                    <strong>Lectura</strong> — <?= e(t('app.tagline')) ?>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span><i class="bi bi-shield-lock" aria-hidden="true"></i> <?= e(t('auth.invite_only')) ?></span>
                    <span><?= date('Y') ?></span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <!-- Lectura client configuration -->
    <?php require __DIR__ . '/client-config.php'; ?>

    <!-- Lectura App JS -->
    <script src="<?= e(asset_url('js/app.js')) ?>"></script>

    <?= $extraJs ?? '' ?>

    <div id="lcLiveStatus" class="visually-hidden" role="status" aria-live="polite" aria-atomic="true"></div>
    <?php if (($livePage ?? '') !== ''): ?>
        <script src="<?= e(asset_url('js/live.js')) ?>"></script>
    <?php endif; ?>
</body>
</html>