(function () {
    const tokenMap = {
        'fa-arrow-left': 'lucide:arrow-left',
        'fa-arrow-right': 'lucide:arrow-right',
        'fa-bars': 'lucide:menu',
        'fa-bell': 'lucide:bell',
        'fa-bolt': 'lucide:zap',
        'fa-bridge': 'lucide:bridge',
        'fa-briefcase': 'lucide:briefcase-business',
        'fa-building': 'lucide:building-2',
        'fa-building-columns': 'lucide:landmark',
        'fa-bullhorn': 'lucide:megaphone',
        'fa-calendar': 'lucide:calendar-days',
        'fa-calendar-check': 'lucide:calendar-check-2',
        'fa-calendar-week': 'lucide:calendar-range',
        'fa-certificate': 'lucide:badge-check',
        'fa-chart-line': 'lucide:chart-line',
        'fa-chart-pie': 'lucide:chart-pie',
        'fa-check-circle': 'lucide:circle-check-big',
        'fa-chevron-down': 'lucide:chevron-down',
        'fa-chevron-left': 'lucide:chevron-left',
        'fa-chevron-right': 'lucide:chevron-right',
        'fa-chevron-up': 'lucide:chevron-up',
        'fa-church': 'mdi:church',
        'fa-circle-check': 'lucide:badge-check',
        'fa-city': 'lucide:building',
        'fa-clipboard-list': 'lucide:clipboard-list',
        'fa-clock': 'lucide:clock-3',
        'fa-cloud-upload-alt': 'lucide:cloud-upload',
        'fa-cog': 'lucide:settings-2',
        'fa-cogs': 'lucide:settings',
        'fa-coins': 'lucide:coins',
        'fa-compass': 'lucide:compass',
        'fa-copyright': 'lucide:copyright',
        'fa-database': 'lucide:database',
        'fa-diagram-project': 'lucide:git-branch-plus',
        'fa-download': 'lucide:download',
        'fa-drafting-compass': 'lucide:compass',
        'fa-earth-asia': 'lucide:earth',
        'fa-edit': 'lucide:square-pen',
        'fa-pen-to-square': 'lucide:square-pen',
        'fa-envelope': 'lucide:mail',
        'fa-envelope-open-text': 'lucide:mail-open',
        'fa-exclamation-circle': 'lucide:circle-alert',
        'fa-exclamation-triangle': 'lucide:triangle-alert',
        'fa-eye': 'lucide:eye',
        'fa-eye-slash': 'lucide:eye-off',
        'fa-facebook': 'simple-icons:facebook',
        'fa-facebook-f': 'simple-icons:facebook',
        'fa-file': 'lucide:file',
        'fa-file-alt': 'lucide:file-text',
        'fa-file-arrow-up': 'lucide:file-up',
        'fa-file-circle-check': 'lucide:file-check-2',
        'fa-file-contract': 'lucide:file-badge-2',
        'fa-file-lines': 'lucide:file-text',
        'fa-file-shield': 'lucide:shield-check',
        'fa-file-signature': 'lucide:file-signature',
        'fa-flag-checkered': 'lucide:flag',
        'fa-folder': 'lucide:folder',
        'fa-folder-open': 'lucide:folder-open',
        'fa-football-ball': 'mdi:football',
        'fa-gears': 'lucide:settings',
        'fa-gem': 'lucide:sparkles',
        'fa-globe': 'lucide:globe',
        'fa-graduation-cap': 'lucide:graduation-cap',
        'fa-handshake': 'lucide:handshake',
        'fa-hands-helping': 'lucide:hand-helping',
        'fa-hard-hat': 'lucide:hard-hat',
        'fa-helmet-safety': 'lucide:hard-hat',
        'fa-history': 'lucide:history',
        'fa-hourglass-half': 'lucide:hourglass',
        'fa-image': 'lucide:image',
        'fa-inbox': 'lucide:inbox',
        'fa-industry': 'lucide:factory',
        'fa-info-circle': 'lucide:info',
        'fa-instagram': 'simple-icons:instagram',
        'fa-key': 'lucide:key-round',
        'fa-link': 'lucide:link',
        'fa-linkedin-in': 'simple-icons:linkedin',
        'fa-list-ul': 'lucide:list',
        'fa-location-dot': 'lucide:map-pinned',
        'fa-lock': 'lucide:lock',
        'fa-map-marker-alt': 'lucide:map-pin',
        'fa-moon': 'lucide:moon',
        'fa-mountain': 'lucide:mountain',
        'fa-newspaper': 'lucide:newspaper',
        'fa-panorama': 'lucide:panels-top-left',
        'fa-paperclip': 'lucide:paperclip',
        'fa-paper-plane': 'lucide:send',
        'fa-people-carry-box': 'lucide:package-check',
        'fa-phone': 'lucide:phone',
        'fa-phone-volume': 'lucide:phone-call',
        'fa-photo-film': 'lucide:gallery-vertical-end',
        'fa-photo-video': 'lucide:images',
        'fa-plug': 'lucide:plug-zap',
        'fa-plus': 'lucide:plus',
        'fa-print': 'lucide:printer',
        'fa-question-circle': 'lucide:circle-help',
        'fa-quick': 'lucide:rocket',
        'fa-road': 'lucide:route',
        'fa-rotate-right': 'lucide:rotate-cw',
        'fa-ruler-combined': 'lucide:ruler',
        'fa-save': 'lucide:save',
        'fa-screwdriver-wrench': 'lucide:wrench',
        'fa-search': 'lucide:search',
        'fa-share-alt': 'lucide:share-2',
        'fa-share-nodes': 'lucide:workflow',
        'fa-shield-alt': 'lucide:shield-check',
        'fa-sign-in-alt': 'lucide:log-in',
        'fa-sign-out-alt': 'lucide:log-out',
        'fa-sitemap': 'lucide:network',
        'fa-sort-numeric-down': 'lucide:arrow-down-1-0',
        'fa-spinner': 'lucide:loader-circle',
        'fa-star': 'lucide:star',
        'fa-sun': 'lucide:sun-medium',
        'fa-sync': 'lucide:refresh-cw',
        'fa-sync-alt': 'lucide:refresh-cw',
        'fa-tachometer-alt': 'lucide:layout-dashboard',
        'fa-tag': 'lucide:tag',
        'fa-tasks': 'lucide:list-todo',
        'fa-times': 'lucide:x',
        'fa-toolbox': 'lucide:briefcase-business',
        'fa-trash': 'lucide:trash-2',
        'fa-triangle-exclamation': 'lucide:triangle-alert',
        'fa-truck': 'lucide:truck',
        'fa-truck-monster': 'mdi:dump-truck',
        'fa-truck-moving': 'lucide:truck',
        'fa-truck-ramp-box': 'mdi:truck-delivery-outline',
        'fa-twitter': 'ri:twitter-x-fill',
        'fa-unlink': 'lucide:unlink',
        'fa-upload': 'lucide:upload',
        'fa-up-right-from-square': 'lucide:arrow-up-right',
        'fa-user': 'lucide:user-round',
        'fa-user-circle': 'lucide:circle-user-round',
        'fa-user-cog': 'lucide:user-round-cog',
        'fa-user-hard-hat': 'mdi:account-hard-hat-outline',
        'fa-user-plus': 'lucide:user-plus',
        'fa-users': 'lucide:users',
        'fa-user-tie': 'lucide:badge-check',
        'fa-vial': 'lucide:flask-conical',
        'fa-warehouse': 'lucide:warehouse',
        'fa-water': 'lucide:droplets',
        'fa-whatsapp': 'simple-icons:whatsapp',
        'fa-xmark': 'lucide:x',
        'fa-youtube': 'simple-icons:youtube'
    };

    function getFaToken(el) {
        const classes = Array.from(el.classList || []);
        return classes.find((className) => /^fa-[a-z0-9-]+$/i.test(className) && className !== 'fa-spin' && className !== 'fa-brands') || '';
    }

    function renderIcon(el) {
        if (!el || el.closest('.ui-icon-skip')) return;
        const token = getFaToken(el);
        if (!token) return;
        const iconName = tokenMap[token] || 'lucide:circle';
        const isSpinning = el.classList.contains('fa-spin');
        const size = el.dataset.iconSize || el.getAttribute('data-icon-size') || '1em';
        if (el.dataset.uiRenderedToken === token && el.dataset.uiRenderedSpin === String(isSpinning)) return;

        el.classList.add('ui-icon');
        el.classList.toggle('is-spinning', isSpinning);
        el.dataset.uiRenderedToken = token;
        el.dataset.uiRenderedSpin = String(isSpinning);
        el.setAttribute('aria-hidden', el.getAttribute('aria-hidden') || 'true');
        el.innerHTML = `<iconify-icon icon="${iconName}" width="${size}" height="${size}"${isSpinning ? ' class="is-spinning"' : ''}></iconify-icon>`;
    }

    function refreshIcons(root = document) {
        root.querySelectorAll('i[class*="fa-"]').forEach(renderIcon);
        if (root.matches && root.matches('i[class*="fa-"]')) renderIcon(root);
    }

    window.refreshEnterpriseIcons = refreshIcons;

    function boot() {
        refreshIcons(document);

        const observer = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                if (mutation.type === 'attributes' && mutation.target instanceof HTMLElement) {
                    if (mutation.target.matches('i[class*="fa-"]')) renderIcon(mutation.target);
                    continue;
                }

                mutation.addedNodes.forEach((node) => {
                    if (!(node instanceof HTMLElement)) return;
                    if (node.matches('i[class*="fa-"]')) renderIcon(node);
                    refreshIcons(node);
                });
            }
        });

        observer.observe(document.documentElement, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class']
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
})();
