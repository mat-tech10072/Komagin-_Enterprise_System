// ============================================================
// KOMAGIN HR – MAIN JAVASCRIPT
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    // Init Lucide icons
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // --------------------------------------------------------
    // SIDEBAR MOBILE TOGGLE
    // --------------------------------------------------------
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            sidebarOverlay?.classList.toggle('active');
        });
        sidebarOverlay?.addEventListener('click', () => {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('active');
        });
    }

    // --------------------------------------------------------
    // NOTIFICATION DROPDOWN
    // --------------------------------------------------------
    const notifBtn = document.getElementById('notifBtn');
    const notifDropdown = document.getElementById('notifDropdown');

    if (notifBtn && notifDropdown) {
        notifBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            notifDropdown.classList.toggle('open');
            userDropdown?.classList.remove('open');
        });
    }

    // --------------------------------------------------------
    // USER DROPDOWN
    // --------------------------------------------------------
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');

    if (userMenuBtn && userDropdown) {
        userMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('open');
            notifDropdown?.classList.remove('open');
        });
    }

    document.addEventListener('click', () => {
        notifDropdown?.classList.remove('open');
        userDropdown?.classList.remove('open');
    });

    // --------------------------------------------------------
    // MODAL SYSTEM
    // --------------------------------------------------------
    function openModal(id) {
        const overlay = document.getElementById(id);
        if (overlay) {
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal(id) {
        const overlay = document.getElementById(id);
        if (overlay) {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    // Open modal via data-modal-open attribute
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', () => openModal(btn.dataset.modalOpen));
    });

    // Close modal via data-modal-close or .modal-close button
    document.querySelectorAll('[data-modal-close], .modal-close').forEach(btn => {
        btn.addEventListener('click', function () {
            const overlay = this.closest('.modal-overlay');
            if (overlay) {
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    // Close on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function (e) {
            if (e.target === this) {
                this.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    // Expose globally
    window.openModal = openModal;
    window.closeModal = closeModal;

    // --------------------------------------------------------
    // ALERT DISMISS
    // --------------------------------------------------------
    document.querySelectorAll('.btn-close').forEach(btn => {
        btn.addEventListener('click', function () {
            this.closest('.alert')?.remove();
        });
    });

    // Auto-dismiss after 5s
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(alert => {
        setTimeout(() => alert.remove(), 5000);
    });

    // --------------------------------------------------------
    // CONFIRM DIALOGS
    // --------------------------------------------------------
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            const msg = this.dataset.confirm || 'Are you sure?';
            if (!confirm(msg)) e.preventDefault();
        });
    });

    // --------------------------------------------------------
    // FORM VALIDATION FEEDBACK
    // --------------------------------------------------------
    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', function (e) {
            let valid = true;
            this.querySelectorAll('[required]').forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    valid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            if (!valid) e.preventDefault();
        });
    });

    // --------------------------------------------------------
    // GLOBAL SEARCH (live API-powered)
    // --------------------------------------------------------
    const globalSearch = document.getElementById('globalSearch');
    if (globalSearch) {
        let searchTimer;
        let searchResults = null;

        // Create results dropdown
        const dropdown = document.createElement('div');
        dropdown.id = 'globalSearchResults';
        dropdown.style.cssText = 'position:absolute;top:100%;left:0;right:0;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:0 8px 24px rgba(0,0,0,0.12);z-index:500;max-height:360px;overflow-y:auto;display:none;margin-top:4px;';
        globalSearch.parentElement.style.position = 'relative';
        globalSearch.parentElement.appendChild(dropdown);

        const iconMap = { employee:'👤', document:'📄', template:'📋', audit:'🛡️' };

        globalSearch.addEventListener('input', function() {
            const q = this.value.trim();
            clearTimeout(searchTimer);
            if (q.length < 2) { dropdown.style.display = 'none'; return; }

            searchTimer = setTimeout(async () => {
                try {
                    const res = await fetch(`${window.APP_URL}/api/search.php?q=${encodeURIComponent(q)}`);
                    const results = await res.json();
                    if (results.length === 0) {
                        dropdown.innerHTML = '<div style="padding:16px;text-align:center;font-size:0.78rem;color:var(--text-muted);">No results found</div>';
                    } else {
                        dropdown.innerHTML = results.map(r =>
                            `<a href="${r.url}" style="display:flex;align-items:flex-start;gap:10px;padding:10px 14px;border-bottom:1px solid var(--border-light);text-decoration:none;color:var(--text);" class="search-result-item">
                                <span style="font-size:1rem;flex-shrink:0;margin-top:1px;">${iconMap[r.type]||'🔍'}</span>
                                <div>
                                    <div style="font-size:0.8rem;font-weight:600;">${r.title}</div>
                                    <div style="font-size:0.7rem;color:var(--text-muted);">${r.sub}</div>
                                </div>
                            </a>`
                        ).join('');
                    }
                    dropdown.style.display = 'block';
                } catch(e) { dropdown.style.display = 'none'; }
            }, 250);
        });

        globalSearch.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') { dropdown.style.display = 'none'; this.blur(); }
        });

        document.addEventListener('click', function(e) {
            if (!globalSearch.parentElement.contains(e.target)) dropdown.style.display = 'none';
        });

        // Hover styles
        dropdown.addEventListener('mouseover', e => {
            const item = e.target.closest('.search-result-item');
            if (item) item.style.background = 'var(--bg)';
        });
        dropdown.addEventListener('mouseout', e => {
            const item = e.target.closest('.search-result-item');
            if (item) item.style.background = '';
        });
    }

    // --------------------------------------------------------
    // SEARCH FILTER (client-side table search)
    // --------------------------------------------------------
    document.querySelectorAll('[data-table-search]').forEach(input => {
        const targetId = input.dataset.tableSearch;
        const table = document.getElementById(targetId);
        if (!table) return;
        input.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            table.querySelectorAll('tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    });

    // --------------------------------------------------------
    // SELECT ALL CHECKBOXES
    // --------------------------------------------------------
    document.querySelectorAll('[data-check-all]').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const name = this.dataset.checkAll;
            document.querySelectorAll(`input[name="${name}"]`).forEach(cb => {
                cb.checked = this.checked;
            });
        });
    });

    // --------------------------------------------------------
    // ACTIVE SIDEBAR ITEM
    // --------------------------------------------------------
    const currentPath = window.location.pathname;
    document.querySelectorAll('.sidebar-nav-item').forEach(item => {
        const href = item.getAttribute('href');
        if (href && currentPath.includes(href) && href !== '#') {
            item.classList.add('active');
        }
    });

    // --------------------------------------------------------
    // PHOTO PREVIEW
    // --------------------------------------------------------
    document.querySelectorAll('[data-photo-preview]').forEach(input => {
        input.addEventListener('change', function () {
            const targetId = this.dataset.photoPreview;
            const img = document.getElementById(targetId);
            if (img && this.files[0]) {
                const reader = new FileReader();
                reader.onload = e => img.src = e.target.result;
                reader.readAsDataURL(this.files[0]);
            }
        });
    });

    // --------------------------------------------------------
    // DEPENDENT SELECT (Department → Position)
    // --------------------------------------------------------
    const deptSelect = document.getElementById('department_id');
    const posSelect = document.getElementById('position_id');

    if (deptSelect && posSelect) {
        deptSelect.addEventListener('change', function () {
            const deptId = this.value;
            if (!deptId) {
                posSelect.innerHTML = '<option value="">Select position</option>';
                return;
            }
            fetch(`${window.APP_URL}/api/positions.php?department_id=${deptId}`)
                .then(r => r.json())
                .then(data => {
                    posSelect.innerHTML = '<option value="">Select position</option>' +
                        data.map(p => `<option value="${p.id}">${p.title}</option>`).join('');
                });
        });
    }

    // --------------------------------------------------------
    // KIOSK CLOCK
    // --------------------------------------------------------
    const kioskClock = document.getElementById('kioskClock');
    const kioskDate = document.getElementById('kioskDate');

    if (kioskClock) {
        function updateClock() {
            const now = new Date();
            kioskClock.textContent = now.toLocaleTimeString('en-ZA', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
            if (kioskDate) {
                kioskDate.textContent = now.toLocaleDateString('en-ZA', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            }
        }
        updateClock();
        setInterval(updateClock, 1000);
    }

    // --------------------------------------------------------
    // SORTABLE TABLE HEADERS
    // --------------------------------------------------------
    document.querySelectorAll('th[data-sort]').forEach(th => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function () {
            const table = this.closest('table');
            const col = [...this.parentElement.children].indexOf(this);
            const asc = this.classList.toggle('sort-asc');
            this.classList.toggle('sort-desc', !asc);
            const rows = [...table.querySelectorAll('tbody tr')];
            rows.sort((a, b) => {
                const va = a.children[col]?.textContent.trim() || '';
                const vb = b.children[col]?.textContent.trim() || '';
                return asc ? va.localeCompare(vb, undefined, { numeric: true }) : vb.localeCompare(va, undefined, { numeric: true });
            });
            rows.forEach(r => table.querySelector('tbody').appendChild(r));
        });
    });

    // --------------------------------------------------------
    // TOAST NOTIFICATIONS (simple)
    // --------------------------------------------------------
    window.showToast = function (message, type = 'info') {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:8px;';
            document.body.appendChild(container);
        }
        const toast = document.createElement('div');
        toast.className = `alert alert-${type}`;
        toast.style.cssText = 'min-width:280px;max-width:360px;animation:slideUp 0.2s ease;box-shadow:0 4px 16px rgba(0,0,0,0.12);';
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    };

});

// CSS animation for toast
const style = document.createElement('style');
style.textContent = '@keyframes slideUp{from{transform:translateY(16px);opacity:0}to{transform:translateY(0);opacity:1}}';
document.head.appendChild(style);

// ── Sidebar scroll hint ───────────────────────────────────────────────────
(function() {
    const sb   = document.getElementById('sidebar');
    const hint = document.getElementById('sidebarScrollHint');
    if (!sb || !hint) return;

    function updateHint() {
        const atBottom = sb.scrollTop + sb.clientHeight >= sb.scrollHeight - 4;
        const canScroll = sb.scrollHeight > sb.clientHeight;
        hint.style.display = (canScroll && !atBottom) ? 'flex' : 'none';
    }

    sb.addEventListener('scroll', updateHint, { passive: true });
    // Run after fonts & layout settle
    setTimeout(updateHint, 300);
    window.addEventListener('resize', updateHint);
})();
