        </div><!-- .admin-content -->
    </main>

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
