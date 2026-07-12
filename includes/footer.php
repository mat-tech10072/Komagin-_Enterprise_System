    </main><!-- /.page-content -->
</div><!-- /.main-content -->
</div><!-- /.app-wrapper -->

<!-- Lucide Icons — defer so it doesn't block page paint -->
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
<!-- Chart.js — only loaded on pages that use it -->
<?php if (!empty($needsCharts)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
<?php endif; ?>
<!-- Expose APP_URL and a CSRF token for JS modules — the notification
     mark-read/mark-all-read calls are state-changing and must carry this,
     same as every other POST in the app. -->
<script>
window.APP_URL = '<?= APP_URL ?>';
window.CSRF_TOKEN = '<?= generateCsrfToken() ?>';
</script>
<!-- Main JS -->
<script src="<?= APP_URL ?>/assets/js/main.js" defer></script>

<!-- Page-specific scripts -->
<?php if (isset($extraScripts)): echo $extraScripts; endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // ── Async notification dropdown ──────────────────────────
    const notifBtn      = document.getElementById('notifBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    const notifList     = document.getElementById('notifList');
    const notifBadgeEl  = document.getElementById('notifBadge');
    let notifLoaded = false;

    function resolveLink(link) {
        if (!link) return null;
        if (link.startsWith('http://') || link.startsWith('https://')) return link;
        return window.APP_URL + link;
    }

    // KOM-093: notification title/message are free text — e.g. the
    // employee-portal Hub lets any logged-in employee set both fields
    // (modules/employee-portal/hub.php's subject/description), and this
    // dropdown is rendered via innerHTML for every user including
    // hr_manager/super_admin. Without this, a low-privilege employee could
    // submit a hub request containing a script payload that executes in an
    // HR Manager's or Super Admin's session the next time they open the
    // notification bell — a stored XSS with a real privilege-escalation path.
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    }

    function updateBadge(count) {
        if (!notifBadgeEl) return;
        notifBadgeEl.textContent = count > 9 ? '9+' : count;
        notifBadgeEl.style.display = count > 0 ? '' : 'none';
    }

    function renderNotifs(data) {
        if (!notifList) return;
        const notifs = data.notifications || [];
        updateBadge(notifs.length);

        if (!notifs.length) {
            notifList.innerHTML = '<div class="notif-item" style="text-align:center;padding:20px;color:var(--text-muted);font-size:0.78rem;">No new notifications</div>';
            return;
        }

        notifList.innerHTML = notifs.map(n => {
            const url = resolveLink(n.link);
            return `<div class="notif-item unread" data-id="${n.id}" data-link="${escapeHtml(url || '')}" style="cursor:pointer;">
                <div class="notif-item-title">${escapeHtml(n.title)}</div>
                <div class="notif-item-msg">${escapeHtml(n.message)}</div>
                <div class="notif-item-time">${escapeHtml(n.time)}</div>
            </div>`;
        }).join('');

        // Click each notification: mark read then navigate
        notifList.querySelectorAll('.notif-item[data-id]').forEach(el => {
            el.addEventListener('click', function() {
                const id   = this.dataset.id;
                const link = this.dataset.link;
                fetch(window.APP_URL + '/api/notifications.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=mark_read&id=' + encodeURIComponent(id) + '&csrf_token=' + encodeURIComponent(window.CSRF_TOKEN)
                }).catch(() => {});
                // Remove from list immediately and update badge
                this.remove();
                const remaining = notifList.querySelectorAll('.notif-item[data-id]').length;
                updateBadge(remaining);
                if (!remaining) {
                    notifList.innerHTML = '<div class="notif-item" style="text-align:center;padding:20px;color:var(--text-muted);font-size:0.78rem;">No new notifications</div>';
                }
                if (link) window.location.href = link;
            });
        });
    }

    function loadNotifs() {
        fetch(window.APP_URL + '/api/notifications.php?action=list')
            .then(r => r.json()).then(renderNotifs).catch(() => {});
    }

    if (notifBtn) {
        notifBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const open = notifDropdown.classList.toggle('show');
            if (open) { loadNotifs(); notifLoaded = true; }
        });
    }

    const markAllBtn = document.getElementById('markAllReadBtn');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            fetch(window.APP_URL + '/api/notifications.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=mark_all_read&csrf_token=' + encodeURIComponent(window.CSRF_TOKEN)
            }).then(r => r.json()).then(() => { notifLoaded = false; loadNotifs(); }).catch(() => {});
        });
    }

    document.addEventListener('click', function(e) {
        if (notifDropdown && !notifDropdown.contains(e.target) && notifBtn && !notifBtn.contains(e.target)) {
            notifDropdown.classList.remove('show');
        }
    });

    // Refresh badge count every 60s without opening dropdown
    setInterval(() => {
        fetch(window.APP_URL + '/api/notifications.php?action=count')
            .then(r => r.json()).then(d => updateBadge(d.count))
            .catch(() => {});
    }, 60000);
});
</script>

</body>
</html>
