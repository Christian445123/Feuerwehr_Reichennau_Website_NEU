        </div><!-- .admin-content -->
    </main>

    <!-- Vollbild-Bestätigungsdialog für gefährliche Aktionen (z.B. Löschen) -->
    <div class="admin-confirm-overlay" id="adminConfirmOverlay">
        <div class="admin-confirm-box">
            <div class="admin-confirm-icon"><i class="fas fa-triangle-exclamation"></i></div>
            <h3 id="adminConfirmTitle"></h3>
            <p id="adminConfirmText"></p>
            <div class="admin-confirm-actions">
                <button type="button" class="btn btn-secondary" id="adminConfirmCancel">Abbrechen</button>
                <a href="#" class="btn btn-danger" id="adminConfirmOk"></a>
            </div>
        </div>
    </div>

    <script>
    // Vollbild-Bestätigung statt des kleinen Browser-confirm() - jeder Link
    // mit data-confirm-title/-text führt beim Klick erst zu diesem Dialog,
    // das eigentliche Ziel (href) wird erst nach "Ja" aufgerufen.
    (function () {
        var overlay = document.getElementById('adminConfirmOverlay');
        var titleEl = document.getElementById('adminConfirmTitle');
        var textEl = document.getElementById('adminConfirmText');
        var okBtn = document.getElementById('adminConfirmOk');
        var cancelBtn = document.getElementById('adminConfirmCancel');

        document.addEventListener('click', function (e) {
            var link = e.target.closest('[data-confirm-title]');
            if (!link) return;
            e.preventDefault();
            titleEl.textContent = link.getAttribute('data-confirm-title');
            textEl.textContent = link.getAttribute('data-confirm-text') || '';
            okBtn.textContent = link.getAttribute('data-confirm-ok') || 'Bestätigen';
            okBtn.href = link.getAttribute('href');
            overlay.classList.add('active');
        });

        cancelBtn.addEventListener('click', function () { overlay.classList.remove('active'); });
        overlay.addEventListener('click', function (e) { if (e.target === overlay) overlay.classList.remove('active'); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') overlay.classList.remove('active'); });
    })();
    </script>

    <script>
    // Sidebar Toggle
    document.getElementById('sidebarToggle')?.addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('open');
        document.querySelector('.admin-main').classList.toggle('sidebar-open');
    });

    // Auto-hide alerts after 5s
    document.querySelectorAll('.alert').forEach(function(el) {
        setTimeout(function() { el.style.opacity = '0'; setTimeout(function() { el.remove(); }, 300); }, 5000);
    });
    </script>
</body>
</html>
